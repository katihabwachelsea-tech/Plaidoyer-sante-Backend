<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    /**
     * POST /api/device-token
     * Enregistre ou met à jour le token FCM de l'appareil connecté.
     * Appelé par Flutter au démarrage et quand FCM rafraîchit le token.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'token'    => 'required|string',
            'platform' => 'nullable|in:android,ios',
        ]);

        DeviceToken::upsert(
            $request->user()->id,
            $validated['token'],
            $validated['platform'] ?? 'android'
        );

        return response()->json([
            'status'  => true,
            'message' => 'Token enregistré.',
        ]);
    }

    /**
     * DELETE /api/device-token
     * Supprime le token FCM à la déconnexion.
     */
    public function remove(Request $request)
    {
        $request->validate(['token' => 'required|string']);

        DeviceToken::where('user_id', $request->user()->id)
            ->where('token', $request->token)
            ->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Token supprimé.',
        ]);
    }
}
