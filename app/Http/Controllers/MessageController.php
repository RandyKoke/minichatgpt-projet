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

                $stream = $this->chatService->streamMessage(
                    $request->message,
                    $conversation->model_used,
                    Auth::user(),
                    $context
                );

                $streamContent = $stream->body();
                $lines = explode("\n", $streamContent);

                foreach ($lines as $line) {
                    if (strpos($line, 'data: ') === 0) {
                        $data = substr($line, 6);

                        if ($data === '[DONE]') {
                            break;
                        }

                        try {
                            $json = json_decode($data, true);
                            if (isset($json['choices'][0]['delta']['content'])) {
                                $content = $json['choices'][0]['delta']['content'];
                                $fullResponse .= $content;

                                echo "data: " . json_encode(['content' => $content]) . "\n\n";
                                ob_flush();
                                flush();

                                usleep(120000); // 120ms entre chaque token
                            }
                        } catch (\Exception $e) {

                        }
                    }
                }

                $conversation->messages()->create([
                    'role' => 'assistant',
                    'content' => $fullResponse,
                    'is_streaming' => true,
                ]);

                $conversation->touch();

                echo "data: " . json_encode(['done' => true]) . "\n\n";
            } catch (\Exception $e) {
                Log::error('Erreur streaming: ' . $e->getMessage());
                echo "data: " . json_encode(['error' => 'Erreur lors de la génération de la réponse']) . "\n\n";
            }

            ob_flush();
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }


      // Ma méthode pour le streaming du titre en temps réel

    public function streamTitle(Request $request, Conversation $conversation)
    {
        Gate::authorize('update', $conversation);

        $firstMessage = $conversation->messages()->where('role', 'user')->first();

        if (!$firstMessage) {
            return response()->json(['error' => 'Aucun message utilisateur trouvé'], 400);
        }

        return new StreamedResponse(function () use ($conversation, $firstMessage) {
            $fullTitle = '';

            try {
                $stream = $this->chatService->streamConversationTitle($firstMessage->content);

                $streamContent = $stream->body();
                $lines = explode("\n", $streamContent);

                foreach ($lines as $line) {
                    if (strpos($line, 'data: ') === 0) {
                        $data = substr($line, 6);

                        if ($data === '[DONE]') {
                            break;
                        }

                        try {
                            $json = json_decode($data, true);
                            if (isset($json['choices'][0]['delta']['content'])) {
                                $content = $json['choices'][0]['delta']['content'];
                                $fullTitle .= $content;

                                echo "data: " . json_encode(['title' => $content]) . "\n\n";
                                ob_flush();
                                flush();

                                // 200ms par mot
                                usleep(200000);
                            }
                        } catch (\Exception $e) {
                            // Important d'ignorer les erreurs de parsing JSON
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
                // Fallback vers le titre qui est généré normalement
                $fallbackTitle = $this->chatService->generateConversationTitle($firstMessage->content);
                $conversation->update([
                    'title' => $fallbackTitle,
                    'title_generated' => true
                ]);
                echo "data: " . json_encode(['done' => true, 'finalTitle' => $fallbackTitle]) . "\n\n";
            }

            ob_flush();
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }


     // Nettoie et valide le titre streamé

    private function cleanAndValidateTitle(string $title, string $fallbackMessage): string
    {
        // Supprimer la ponctuation pour que les titres soient plus cohérents
        $title = preg_replace('/["\'\(\)\[\]{}.,;:!?\/\\\\]/', '', $title);
        $title = preg_replace('/\s+/', ' ', trim($title));

        // Je limite à 4 mots maximum
        $words = explode(' ', $title);
        if (count($words) > 4) {
            $title = implode(' ', array_slice($words, 0, 4));
        }

        // Si le titre est vide ou trop court, on utilise un fallback
        if (strlen($title) < 3) {
            return $this->chatService->generateConversationTitle($fallbackMessage);
        }

        return $title;
    }
}
