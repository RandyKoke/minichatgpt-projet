<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatService
{
    private string $apiKey;

    private string $baseUrl;
    private string $apiversion ='v1';

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
    }

    public function getAvailableModels(): array
    {
        return [
            'gpt-4o' => 'GPT-4o (OpenAI)',
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo (OpenAI)',
            'claude-3-sonnet-20240229' => 'Claude 3 Sonnet (Anthropic)',
            'google/gemini-pro' => 'Gemini Pro (Google)',
            'meta-llama/llama-2-70b-chat' => 'Llama 2 70B (Meta)',
        ];
    }


      // On génère des titres intelligents et pertinents (de maximum 4 MOTS)

    public function generateConversationTitle(string $firstMessage): string
    {
        try {
            $response = $this->makeApiCall([
                'model' => $this->mapModel('gpt-3.5-turbo'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Génère un titre TRÈS court de maximum 4 mots pour cette question. Le titre doit être naturel, informatif et sans ponctuation.

EXEMPLES PARFAITS :
"Comment s\'appelle la femelle du bouc ?" → "Femelle du bouc"
"Quelle est la capitale du Canada ?" → "Capitale du Canada"
"Comment créer une API Laravel ?" → "Créer API Laravel"
"Pourquoi le ciel est gris ?" → "Couleur du ciel"

Réponds UNIQUEMENT avec le titre, sans guillemets, sans ponctuation.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $firstMessage
                    ]
                ],
                'max_tokens' => 15,
                'temperature' => 0.2,
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


     // Le stream du titre en temps réel

    public function streamConversationTitle(string $firstMessage)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'text/event-stream',
                'HTTP-Referer' => config('app.url'),
                'X-Title' => 'MiniChatGPT'
            ])
            ->timeout(120)
            ->post($this->baseUrl . '/chat/completions', [
                'model' => $this->mapModel('gpt-3.5-turbo'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Génère un titre très court de maximum 4 mots pour cette question. Écris le titre mot par mot, lentement.'
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

            return $response;
        } catch (\Exception $e) {
            Log::error('Erreur streaming titre: ' . $e->getMessage());
            throw $e;
        }
    }

    private function mapModel(string $model): string
    {
        return $this->modelMapping[$model] ?? $model;
    }

    public function streamMessage(string $message, string $model = 'gpt-3.5-turbo', ?User $user = null, array $context = [])
    {
        $messages = $this->buildMessageContext($user, $context, $message);
        $mappedModel = $this->mapModel($model);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'text/event-stream',
                'HTTP-Referer' => config('app.url'),
                'X-Title' => 'MiniChatGPT'
            ])
            ->timeout(120)
            ->post($this->baseUrl . '/chat/completions', [
                'model' => $mappedModel,
                'messages' => $messages,
                'max_tokens' => 2000,
                'temperature' => 0.7,
                'stream' => true,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Erreur ChatService Stream: ' . $e->getMessage());
            throw $e;
        }
    }

    public function sendMessage(string $message, string $model = 'gpt-3.5-turbo', ?User $user = null, array $context = []): string
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
            return "Désolé, une erreur s'est produite lors de la génération de la réponse.";
        }
    }

    private function makeApiCall(array $payload): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'HTTP-Referer' => config('app.url'),
            'X-Title' => 'MiniChatGPT'
        ])
        ->timeout(60)
        ->post($this->baseUrl . '/chat/completions', $payload);

        if (!$response->successful()) {
            Log::error('API Error: ' . $response->body());
            throw new \Exception('API call failed: ' . $response->body());
        }

        return $response->json();
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
                'pourquoi', 'où', 'quand', 'qui', 'dont', 'lequel', 'dans', 'avec', 'pour'
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
