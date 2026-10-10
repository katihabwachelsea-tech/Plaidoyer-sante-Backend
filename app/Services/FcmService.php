<?php

namespace App\Services;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service d'envoi de notifications push via Firebase FCM HTTP v1.
 *
 * Configuration requise dans .env :
 *   FCM_SERVER_KEY=AAAA...  (clé serveur Firebase — Legacy HTTP API)
 *
 * Pour obtenir la clé :
 *   Firebase Console → Projet → Paramètres → Cloud Messaging → Clé du serveur
 */
class FcmService
{
    private const FCM_URL = 'https://fcm.googleapis.com/fcm/send';

    /**
     * Envoie une notification push à tous les appareils d'un utilisateur.
     */
    public static function sendToUser(
        int $userId,
        string $title,
        string $body,
        array $data = []
    ): void {
        $serverKey = config('services.fcm.server_key');

        if (empty($serverKey)) {
            Log::warning('FCM: FCM_SERVER_KEY non configurée — push ignorée.');
            return;
        }

        $tokens = DeviceToken::where('user_id', $userId)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return; // Pas d'appareil enregistré pour cet utilisateur
        }

        $payload = [
            'registration_ids' => $tokens,
            'notification' => [
                'title' => $title,
                'body'  => $body,
                'sound' => 'default',
            ],
            'data'     => $data,
            'priority' => 'high',
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $serverKey,
                'Content-Type'  => 'application/json',
            ])->post(self::FCM_URL, $payload);

            $result = $response->json();

            // Nettoyer les tokens invalides retournés par FCM
            if (!empty($result['results'])) {
                foreach ($result['results'] as $index => $res) {
                    if (!empty($res['error']) && in_array($res['error'], [
                        'NotRegistered', 'InvalidRegistration',
                    ])) {
                        DeviceToken::where('token', $tokens[$index])->delete();
                        Log::info('FCM: Token invalide supprimé : ' . $tokens[$index]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('FCM: Erreur envoi push : ' . $e->getMessage());
        }
    }
}
