<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MessageController extends Controller
{
    protected ChatService $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    public function store(Request $request, Conversation $conversation)
    {
        Gate::authorize('update', $conversation);

        $request->validate([
            'message' => 'required|string|max:2000',
            'model' => 'sometimes|string',
        ]);

        try {
            DB::beginTransaction();

            $userMessage = $conversation->messages()->create([
                'role' => 'user',
                'content' => $request->message,
            ]);

            if ($conversation->messages()->count() === 1) {
                $title = $this->chatService->generateConversationTitle($request->message);
                $conversation->update([
                    'title' => $title,
                    'title_generated' => true
                ]);
            }

            if ($request->model) {
                $conversation->update(['model_used' => $request->model]);
            }

            $context = $conversation->messages()
                ->where('id', '<', $userMessage->id)
                ->orderBy('created_at')
                ->get()
                ->map(fn ($msg) => [
                    'role' => $msg->role,
                    'content' => $msg->content
                ])
                ->toArray();

            $response = $this->chatService->sendMessage(
                $request->message,
                $conversation->model_used,
                Auth::user(),
                $context
            );

            $conversation->messages()->create([
                'role' => 'assistant',
                'content' => $response,
            ]);

            $conversation->touch();
            DB::commit();

            return back()->with('message', 'Message envoyé avec succès.');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Erreur envoi message: ' . $e->getMessage());
            return back()->withErrors([
                'error' => 'Une erreur est survenue lors de l\'envoi du message.',
            ]);
        }
    }

    public function stream(Request $request, Conversation $conversation)
    {
        Gate::authorize('update', $conversation);

        $request->validate([
            'message' => 'required|string|max:2000',
            'model' => 'sometimes|string',
        ]);

        return new StreamedResponse(function () use ($request, $conversation) {
            set_time_limit(0);
            ignore_user_abort(false);

            $fullResponse = '';

            try {
                $userMessage = $conversation->messages()->create([
                    'role' => 'user',
                    'content' => $request->message,
                    'is_streaming' => false,
                ]);

                if ($request->model) {
                    $conversation->update(['model_used' => $request->model]);
                }

                $context = $conversation->messages()
                    ->where('id', '<', $userMessage->id)
                    ->orderBy('created_at')
                    ->get()
                    ->map(fn ($msg) => [
                        'role' => $msg->role,
                        'content' => $msg->content
                    ])
                    ->toArray();

                $httpResponse = $this->chatService->streamMessage(
                    $request->message,
                    $conversation->model_used,
                    Auth::user(),
                    $context
                );

                if ($httpResponse->successful()) {
                    $responseBody = $httpResponse->body();
                    $lines = explode("\n", $responseBody);

                    foreach ($lines as $line) {
                        $line = trim($line);

                        if (strpos($line, 'data: ') === 0) {
                            $data = trim(substr($line, 6));

                            if ($data === '[DONE]') {
                                break;
                            }

                            if (empty($data)) continue;

                            try {
                                $json = json_decode($data, true);

                                if (isset($json['choices'][0]['delta']['content'])) {
                                    $content = $json['choices'][0]['delta']['content'];
                                    $fullResponse .= $content;

                                    echo "data: " . json_encode(['content' => $content]) . "\n\n";

                                    if (ob_get_level()) {
                                        ob_flush();
                                    }
                                    flush();

                                    usleep(120000);
                                }

                                if (isset($json['choices'][0]['finish_reason']) && $json['choices'][0]['finish_reason'] === 'stop') {
                                    break;
                                }

                            } catch (\Exception $e) {
                                Log::warning('Erreur parsing JSON stream: ' . $e->getMessage());
                                continue;
                            }
                        }
                    }
                } else {
                    Log::error('Erreur API OpenRouter: ' . $httpResponse->body());
                    echo "data: " . json_encode(['error' => 'Erreur API: ' . $httpResponse->status()]) . "\n\n";
                }

                if (!empty($fullResponse)) {
                    $conversation->messages()->create([
                        'role' => 'assistant',
                        'content' => $fullResponse,
                        'is_streaming' => true,
                    ]);

                    $conversation->touch();
                }

                echo "data: " . json_encode(['done' => true]) . "\n\n";

            } catch (\Exception $e) {
                Log::error('Erreur streaming: ' . $e->getMessage());
                echo "data: " . json_encode(['error' => 'Erreur lors de la génération de la réponse: ' . $e->getMessage()]) . "\n\n";
            }

            if (ob_get_level()) {
                ob_flush();
            }
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function streamTitle(Request $request, Conversation $conversation)
    {
        Gate::authorize('update', $conversation);

        $firstMessage = $conversation->messages()->where('role', 'user')->first();

        if (!$firstMessage) {
            return response()->json(['error' => 'Aucun message utilisateur trouvé'], 400);
        }

        return new StreamedResponse(function () use ($conversation, $firstMessage) {
            set_time_limit(0);

            $fullTitle = '';

            try {
                $httpResponse = $this->chatService->streamConversationTitle($firstMessage->content);

                if ($httpResponse->successful()) {
                    $responseBody = $httpResponse->body();
                    $lines = explode("\n", $responseBody);

                    foreach ($lines as $line) {
                        $line = trim($line);

                        if (strpos($line, 'data: ') === 0) {
                            $data = trim(substr($line, 6));

                            if ($data === '[DONE]') {
                                break;
                            }

                            if (empty($data)) continue;

                            try {
                                $json = json_decode($data, true);

                                if (isset($json['choices'][0]['delta']['content'])) {
                                    $content = $json['choices'][0]['delta']['content'];
                                    $fullTitle .= $content;

                                    echo "data: " . json_encode(['title' => $content]) . "\n\n";

                                    if (ob_get_level()) {
                                        ob_flush();
                                    }
                                    flush();

                                    usleep(200000);
                                }

                                if (isset($json['choices'][0]['finish_reason']) && $json['choices'][0]['finish_reason'] === 'stop') {
                                    break;
                                }

                            } catch (\Exception $e) {
                                Log::warning('Erreur parsing JSON titre: ' . $e->getMessage());
                                continue;
                            }
                        }
                    }
                }

                $finalTitle = $this->cleanAndValidateTitle(trim($fullTitle), $firstMessage->content);

                $conversation->update([
                    'title' => $finalTitle,
                    'title_generated' => true
                ]);

                echo "data: " . json_encode(['done' => true, 'finalTitle' => $finalTitle]) . "\n\n";

            } catch (\Exception $e) {
                Log::error('Erreur streaming titre: ' . $e->getMessage());

                // Utilisation du service pour générer un titre de fallback intelligent
                try {
                    $fallbackTitle = $this->chatService->generateConversationTitle($firstMessage->content);
                } catch (\Exception $fallbackError) {
                    // Si même le fallback échoue, on utilise le début du message
                    $fallbackTitle = $this->generateSimpleFallbackTitle($firstMessage->content);
                }

                $conversation->update([
                    'title' => $fallbackTitle,
                    'title_generated' => true
                ]);
                echo "data: " . json_encode(['done' => true, 'finalTitle' => $fallbackTitle]) . "\n\n";
            }

            if (ob_get_level()) {
                ob_flush();
            }
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function cleanAndValidateTitle(string $title, string $fallbackMessage): string
    {
        // Nettoyage moins agressif
        $title = preg_replace('/["\'\[\]{}.,;:!?\/\\\\]/', '', $title);
        $title = preg_replace('/\s+/', ' ', trim($title));

        $words = explode(' ', $title);
        if (count($words) > 5) {
            $title = implode(' ', array_slice($words, 0, 5));
        }

        // Validation moins stricte : ici on accepte même les titres courts
        if (strlen(trim($title)) < 2) {
            try {
                return $this->chatService->generateConversationTitle($fallbackMessage);
            } catch (\Exception $e) {
                return $this->generateSimpleFallbackTitle($fallbackMessage);
            }
        }

        return $title;
    }

    private function generateSimpleFallbackTitle(string $message): string
    {
        // Extraction des mots clés importants
        $words = explode(' ', trim($message));
        $keyWords = array_filter($words, function($word) {
            return strlen($word) > 3 && !in_array(strtolower($word), [
                'comment', 'quoi', 'que', 'est-ce', 'quel', 'quelle', 'quels', 'quelles',
                'pourquoi', 'où', 'quand', 'qui', 'dont', 'lequel', 'dans', 'avec', 'pour',
                'what', 'how', 'when', 'where', 'why', 'who', 'which', 'waar', 'wanner'
            ]);
        });

        $title = implode(' ', array_slice($keyWords, 0, 3));
        return $title ?: substr($message, 0, 30) . (strlen($message) > 30 ? '...' : '');
    }
}
