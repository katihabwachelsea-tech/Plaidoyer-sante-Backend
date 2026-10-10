<?php

namespace App\Models;

use App\Services\FcmService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppNotification extends Model
{
    protected $table = 'app_notifications';

    protected $fillable = [
        'user_id',
        'title',
        'body',
        'type',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data'    => 'array',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Crée une notification in-app ET envoie la push FCM si un token existe.
     *
     * @param int    $userId  Destinataire
     * @param string $title   Titre de la notification
     * @param string $body    Corps du message
     * @param string $type    Type métier (appointment_accepted, consultation_done, etc.)
     * @param array|null $data Données supplémentaires (ex: appointment_id)
     */
    public static function notify(
        int $userId,
        string $title,
        string $body,
        string $type = 'info',
        ?array $data = null
    ): self {
        // 1. Notification in-app (base de données)
        $notif = self::create([
            'user_id' => $userId,
            'title'   => $title,
            'body'    => $body,
            'type'    => $type,
            'data'    => $data,
        ]);

        // 2. Push FCM (silencieux si clé non configurée)
        FcmService::sendToUser($userId, $title, $body, array_merge(
            $data ?? [],
            ['type' => $type, 'notification_id' => $notif->id]
        ));

        return $notif;
    }
}
