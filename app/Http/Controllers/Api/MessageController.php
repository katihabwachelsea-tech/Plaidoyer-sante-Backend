<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Appointment;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * GET /api/appointments/{id}/messages
     * Retourne tous les messages du RDV + marque les messages reçus comme lus.
     */
    public function index(Request $request, int $appointmentId)
    {
        $user = $request->user();
        $appt = $this->getAppointment($appointmentId, $user->id);

        if (!$appt) {
            return response()->json(['status' => false, 'message' => 'RDV introuvable.'], 404);
        }

        // Marquer les messages de l'autre comme lus
        Message::where('appointment_id', $appointmentId)
            ->where('sender_id', '!=', $user->id)
            ->whereNull('lu_at')
            ->update(['lu_at' => now()]);

        $messages = Message::where('appointment_id', $appointmentId)
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($m) => $m->format($user->id));

        return response()->json([
            'status' => true,
            'data'   => $messages,
        ]);
    }

    /**
     * POST /api/appointments/{id}/messages
     * Envoie un message dans la conversation du RDV.
     */
    public function store(Request $request, int $appointmentId)
    {
        $user = $request->user();
        $appt = $this->getAppointment($appointmentId, $user->id);

        if (!$appt) {
            return response()->json(['status' => false, 'message' => 'RDV introuvable.'], 404);
        }

        // Messagerie disponible tant que le RDV n'est pas annulé
        if ($appt->statut === 'Annule') {
            return response()->json([
                'status'  => false,
                'message' => 'La messagerie n\'est pas disponible pour un RDV annulé.',
            ], 422);
        }

        $validated = $request->validate([
            'contenu' => 'required|string|max:2000',
        ]);

        $message = Message::create([
            'appointment_id' => $appointmentId,
            'sender_id'      => $user->id,
            'contenu'        => trim($validated['contenu']),
        ]);
        $message->load('sender');

        // Notifier le destinataire
        $recipientId = $user->id === $appt->patient_id
            ? $appt->medecin_id
            : $appt->patient_id;

        AppNotification::notify(
            (int) $recipientId,
            'Nouveau message',
            $user->nom . ' : ' . \Illuminate\Support\Str::limit($validated['contenu'], 60),
            'new_message',
            ['appointment_id' => $appointmentId]
        );

        return response()->json([
            'status' => true,
            'data'   => $message->format($user->id),
        ], 201);
    }

    /**
     * GET /api/appointments/{id}/messages/unread-count
     * Nombre de messages non lus pour l'utilisateur connecté.
     */
    public function unreadCount(Request $request, int $appointmentId)
    {
        $user  = $request->user();
        $count = Message::where('appointment_id', $appointmentId)
            ->where('sender_id', '!=', $user->id)
            ->whereNull('lu_at')
            ->count();

        return response()->json(['status' => true, 'count' => $count]);
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function getAppointment(int $id, int $userId): ?Appointment
    {
        return Appointment::where('id', $id)
            ->where(function ($q) use ($userId) {
                $q->where('patient_id', $userId)
                  ->orWhere('medecin_id', $userId);
            })
            ->first();
    }
}
