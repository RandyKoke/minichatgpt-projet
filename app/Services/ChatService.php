<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatService
{
    private string $apiKey;
    private string $baseUrl;
    private array $modelMapping = [
        'gpt-4o' => 'openai/gpt-4o',
        'gpt-3.5-turbo' => 'openai/gpt-3.5-turbo',
        'claude-3-sonnet-20240229' => 'anthropic/claude-3-sonnet-20240229',
        'google/gemini-pro' => 'google/gemini-pro',
        'meta-llama/llama-2-70b-chat' => 'meta-llama/llama-2-70b-chat',
    ];

    public function __construct()
    {
        $this->apiKey = config('openai.api_key') ?: env('OPENAI_API_KEY');
        $this->baseUrl = config('openai.base_uri') ?: env('OPENAI_BASE_URI', 'https://openrouter.ai/api/v1');

        if (empty($this->apiKey)) {
            Log::error('Clé API OpenRouter manquante');
        }
    }

    public function getAvailableModels(): array
    {
        return [
            'gpt-4o' => 'GPT-4o (OpenAI)',
            'claude-3-sonnet-20240229' => 'Claude 3 Sonnet (Anthropic)',
            'google/gemini-pro' => 'Gemini Pro (Google)',
            'meta-llama/llama-2-70b-chat' => 'Llama 2 70B (Meta)',
        ];
    }

    public function generateConversationTitle(string $firstMessage): string
    {
        try {
            $response = $this->makeApiCall([
                'model' => $this->mapModel('gpt-4o'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Tu es un expert en création de titres courts. Génère un titre de 2 à 4 mots maximum qui résume parfaitement la question posée.

EXEMPLES PARFAITS:
"Quelle est la capitale de la France ?" → "Capitale France"
"Comment faire du pain ?" → "Recette Pain"
"Qui a inventé l\'ordinateur ?" → "Inventeur Ordinateur"
"Quelle est la deuxième ville la plus peuplée de la Belgique après Bruxelles ?" → "Anvers Belgique"

RÈGLES:
- Maximum 4 mots
- Pas de ponctuation
- Mots clés essentiels seulement
- Réponse directe sans explication

Question:'
                    ],
                    [
                        'role' => 'user',
                        'content' => $firstMessage
                    ]
                ],
                'max_tokens' => 12,
                'temperature' => 0.1,
            ]);

            $title = trim($response['choices'][0]['message']['content'] ?? '');
            $title = $this->cleanTitle($title);

            $words = explode(' ', $title);
            if (count($words) > 4) {
                $title = implode(' ', array_slice($words, 0, 4));
            }

            return $title ?: $this->generateFallbackTitle($firstMessage);

        } catch (\Exception $e) {
            Log::error('Erreur génération titre: ' . $e->getMessage());
            return $this->generateFallbackTitle($firstMessage);
        }
    }

    public function streamConversationTitle(string $firstMessage)
    {
        try {
            return Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'text/event-stream',
                'HTTP-Referer' => config('app.url', 'http://localhost'),
                'X-Title' => 'MiniChatGPT'
            ])
            ->timeout(60)
            ->connectTimeout(10)
            ->post($this->baseUrl . '/chat/completions', [
                'model' => $this->mapModel('gpt-4o'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Crée un titre très court de 2-4 mots pour cette question. Écris chaque mot lentement.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $firstMessage
                    ]
                ],
                'max_tokens' => 12,
                'temperature' => 0.1,
                'stream' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur streaming titre: ' . $e->getMessage());
            throw $e;
        }
    }

    public function streamMessage(string $message, string $model = 'gpt-4o', ?User $user = null, array $context = [])
    {
        $messages = $this->buildMessageContext($user, $context, $message);
        $mappedModel = $this->mapModel($model);

        Log::info('Début streaming avec modèle: ' . $mappedModel);

        try {
            return Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'text/event-stream',
                'HTTP-Referer' => config('app.url', 'http://localhost'),
                'X-Title' => 'MiniChatGPT'
            ])
            ->timeout(120)
            ->connectTimeout(10)
            ->post($this->baseUrl . '/chat/completions', [
                'model' => $mappedModel,
                'messages' => $messages,
                'max_tokens' => 2000,
                'temperature' => 0.7,
                'stream' => true,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur ChatService Stream: ' . $e->getMessage());
            throw $e;
        }
    }

    public function sendMessage(string $message, string $model = 'gpt-4o', ?User $user = null, array $context = []): string
    {
        $messages = $this->buildMessageContext($user, $context, $message);
        $mappedModel = $this->mapModel($model);

        try {
            $response = $this->makeApiCall([
                'model' => $mappedModel,
                'messages' => $messages,
                'max_tokens' => 2000,
                'temperature' => 0.7,
            ]);

            return $response['choices'][0]['message']['content'] ?? "Erreur lors de la génération de la réponse.";

        } catch (\Exception $e) {
            Log::error('Erreur ChatService: ' . $e->getMessage());
            return "Désolé, une erreur s'est produite lors de la génération de la réponse: " . $e->getMessage();
        }
    }

    private function makeApiCall(array $payload): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'HTTP-Referer' => config('app.url', 'http://localhost'),
            'X-Title' => 'MiniChatGPT'
        ])
        ->timeout(60)
        ->connectTimeout(10)
        ->post($this->baseUrl . '/chat/completions', $payload);

        if (!$response->successful()) {
            Log::error('API Error: ' . $response->status() . ' - ' . $response->body());
            throw new \Exception('API call failed: ' . $response->status() . ' - ' . $response->body());
        }

        return $response->json();
    }

    private function mapModel(string $model): string
    {
        return $this->modelMapping[$model] ?? $model;
    }

    private function cleanTitle(string $title): string
    {
        $title = preg_replace('/["\'\(\)\[\]{}.,;:!?\/\\\\]/', '', $title);
        $title = preg_replace('/\s+/', ' ', trim($title));
        return $title;
    }

    private function generateFallbackTitle(string $message): string
    {
        $words = explode(' ', trim($message));
        $keyWords = array_filter($words, function($word) {
            return strlen($word) > 3 && !in_array(strtolower($word), [
                'comment', 'quoi', 'que', 'est-ce', 'quel', 'quelle', 'quels', 'quelles',
                'pourquoi', 'où', 'quand', 'qui', 'dont', 'lequel', 'dans', 'avec', 'pour',
                'the', 'what', 'how', 'when', 'where', 'why', 'who'
            ]);
        });

        $title = implode(' ', array_slice($keyWords, 0, 3));
        return $title ?: 'Nouvelle conversation';
    }

    private function buildMessageContext(?User $user, array $context, string $message): array
    {
        $messages = [];

        if ($user && $user->customInstruction && $user->customInstruction->is_active) {
            $systemMessage = $user->customInstruction->buildSystemPrompt();
            if ($systemMessage) {
                $messages[] = ['role' => 'system', 'content' => $systemMessage];
            }
        }

        foreach ($context as $msg) {
            $messages[] = [
                'role' => $msg['role'],
                'content' => $msg['content']
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $message];

        return $messages;
    }
}
