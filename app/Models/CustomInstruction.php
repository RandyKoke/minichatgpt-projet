<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomInstruction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'about_you',
        'assistant_behavior',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Méthode pour construire le prompt système
    public function buildSystemPrompt(): string
    {
        $prompt = '';

        if ($this->about_you) {
            $prompt .= "À propos de l'utilisateur : " . $this->about_you . "\n\n";
        }

        if ($this->assistant_behavior) {
            $prompt .= "Comportement souhaité : " . $this->assistant_behavior;
        }

        return trim($prompt);
    }
}
