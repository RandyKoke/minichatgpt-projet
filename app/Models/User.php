<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasTeams;
    use Notifiable;
    use TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'preferred_model',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    // Les relations pour mon application avec eager loading
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class)->orderBy('updated_at', 'desc');
    }

    public function conversationsWithMessages(): HasMany
    {
        return $this->conversations()->withEagerLoading();
    }

    public function customInstruction(): HasOne
    {
        return $this->hasOne(CustomInstruction::class);
    }

    public function activeCustomInstruction(): HasOne
    {
        return $this->customInstruction()->where('is_active', true);
    }
}
