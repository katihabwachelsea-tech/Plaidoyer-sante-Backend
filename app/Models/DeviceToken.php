<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceToken extends Model
{
    protected $fillable = ['user_id', 'token', 'platform', 'last_used_at'];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Enregistre ou met à jour le token FCM d'un utilisateur.
     */
    public static function upsert(int $userId, string $token, string $platform = 'android'): self
    {
        return self::updateOrCreate(
            ['token' => $token],
            [
                'user_id'      => $userId,
                'platform'     => $platform,
                'last_used_at' => now(),
            ]
        );
    }
}
