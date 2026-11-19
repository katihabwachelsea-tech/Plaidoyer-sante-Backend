<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Message;
use Illuminate\Http\Request;

/**
 * GET /api/conversations
 *
 * Retourne les conversations liées aux rendez-vous de l'utilisateur, y compris
 * celles sans message afin qu'il puisse démarrer un échange.
 * Triée du plus récent message au plus ancien.
 */
class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Inclure les rendez-vous sans message pour permettre de démarrer un échange.
        $appointments = Appointment::with([
            'patient.user',
            'medecin.user',
            'service',
        ])
            ->where(function ($q) use ($user) {
                $q->where('patient_id', $user->id)
                    ->orWhere('medecin_id', $user->id);
            })
            ->where('statut', '!=', 'Annule')
            ->get();

        $conversations = [];

        foreach ($appointments as $appt) {
            // Dernier message de cette conversation
            $lastMessage = Message::where('appointment_id', $appt->id)
                ->orderBy('created_at', 'desc')
                ->first();

            // Nombre de messages non lus pour l'utilisateur connecté
            $unreadCount = Message::where('appointment_id', $appt->id)
                ->where('sender_id', '!=', $user->id)
                ->whereNull('lu_at')
                ->count();

            // Interlocuteur selon le rôle
            if ((int) $user->id === (int) $appt->patient_id) {
                $other = $appt->medecin?->user;
                $otherRole = 'medecin';
                $otherName = $other?->nom ?? 'Médecin';
                $otherPhoto = $other?->photo_url;
            } else {
                $other = $appt->patient?->user;
                $otherRole = 'patient';
                $otherName = $other?->nom ?? 'Patient';
                $otherPhoto = $other?->photo_url;
            }

            $conversations[] = [
                'appointment_id' => $appt->id,
                'appointment_statut' => $appt->statut,
                'motif' => $appt->motif,
                'date_rdv' => $appt->date_rdv?->format('Y-m-d H:i:s'),
                'service' => $appt->service?->nom_service,
                'other_name' => $otherName,
                'other_role' => $otherRole,
                'other_photo_url' => $otherPhoto,
                'last_message' => $lastMessage?->contenu,
                'last_message_at' => $lastMessage?->created_at?->format('Y-m-d H:i:s')
                    ?? $appt->date_rdv?->format('Y-m-d H:i:s'),
                'last_message_sender_id' => $lastMessage?->sender_id,
                'last_is_mine' => $lastMessage !== null
                    && (int) $lastMessage->sender_id === (int) $user->id,
                'unread_count' => $unreadCount,
            ];
        }

        // Trier par dernier message le plus récent
        usort($conversations, fn ($a, $b) => strcmp($b['last_message_at'] ?? '', $a['last_message_at'] ?? '')
        );

        return response()->json([
            'status' => true,
            'current_user' => [
                'id' => (int) $user->id,
                'nom' => $user->nom,
                'role' => $user->role,
            ],
            'data' => $conversations,
        ]);
    }
}
