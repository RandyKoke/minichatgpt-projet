<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'model_used',
        'title_generated',
    ];

    protected $casts = [
        'title_generated' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Mes relations avec eager loading
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    // les différents scopes pour un eager loading optimisé
    public function scopeWithEagerLoading($query)
    {
        return $query->with(['messages', 'latestMessage']);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Génération automatique du titre via API
    public function generateTitleViaAPI(): void
    {
        if (!$this->title_generated && $this->messages()->where('role', 'user')->exists()) {
            $firstUserMessage = $this->messages()->where('role', 'user')->first();

            if ($firstUserMessage) {
                // Appel à l'API pour générer un titre intelligent
                $chatService = app(\App\Services\ChatService::class);
                $generatedTitle = $chatService->generateConversationTitle($firstUserMessage->content);

                $this->update([
                    'title' => $generatedTitle,
                    'title_generated' => true
                ]);
            }
        }
    }

    // Titre de fallback si l'API ne répond pas
    public function generateFallbackTitle(): void
    {
        $firstMessage = $this->messages()->where('role', 'user')->first();
        if ($firstMessage) {
            $title = substr($firstMessage->content, 0, 50);
            if (strlen($firstMessage->content) > 50) {
                $title .= '...';
            }
            $this->update(['title' => $title]);
        }
    }
}
