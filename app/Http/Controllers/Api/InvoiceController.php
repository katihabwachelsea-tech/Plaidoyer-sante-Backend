<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    /**
     * PATIENT : Payer via facture (délègue les mêmes règles que PatientAppointmentController::pay).
     * POST /api/invoices/{id}/pay
     */
    public function pay(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'patient') {
            return response()->json(['status' => false, 'message' => 'Accès réservé aux patients.'], 403);
        }

        $invoice = Invoice::with(['appointment.service', 'appointment.medecin.user'])->find($id);
        if (!$invoice || !$invoice->appointment) {
            return response()->json(['status' => false, 'message' => 'Facture introuvable.'], 404);
        }

        $appointment = $invoice->appointment;

        if ((int) $appointment->patient_id !== (int) $user->id) {
            return response()->json(['status' => false, 'message' => 'Action non autorisée.'], 403);
        }

        if ($invoice->statut_paiement === 'Paye') {
            return response()->json(['status' => false, 'message' => 'Cette facture est déjà réglée.'], 422);
        }

        if ($appointment->statut === 'Annule') {
            return response()->json(['status' => false, 'message' => 'Ce rendez-vous a été annulé.'], 422);
        }

        if ($appointment->date_rdv <= now()->subHours(2)) {
            return response()->json([
                'status'  => false,
                'message' => 'Ce créneau a expiré. Vous ne pouvez plus le payer.',
            ], 422);
        }

        if ($appointment->statut !== 'Accepte') {
            return response()->json([
                'status'  => false,
                'message' => 'Ce rendez-vous ne peut pas être payé : le médecin doit d\'abord accepter la demande.',
            ], 422);
        }

        $validated = $request->validate([
            'methode_paiement' => 'required|in:Ecocash,Lumicash',
            'telephone'        => 'required|string|min:8',
        ]);

        $transactionId = strtoupper($validated['methode_paiement']) . '-' . now()->format('YmdHis') . '-' . random_int(100, 999);

        DB::transaction(function () use ($invoice, $appointment, $validated, $transactionId) {
            $invoice->update([
                'statut_paiement'  => 'Paye',
                'methode_paiement' => $validated['methode_paiement'],
                'transaction_id'   => $transactionId,
            ]);
            $appointment->update(['statut' => 'Confirme']);
        });

        $label = Carbon::parse($appointment->date_rdv)->locale('fr')->isoFormat('ddd D MMM HH:mm');
        AppNotification::notify(
            $user->id,
            'Rendez-vous confirmé',
            "Paiement reçu. RDV confirmé le $label.",
            'appointment_confirmed',
            ['appointment_id' => $appointment->id]
        );
        AppNotification::notify(
            (int) $appointment->medecin_id,
            'RDV confirmé',
            ($user->nom ?? 'Un patient')." a payé — RDV le $label.",
            'appointment_confirmed',
            ['appointment_id' => $appointment->id]
        );

        return response()->json([
            'status'         => true,
            'message'        => 'Paiement réussi ! Votre rendez-vous est maintenant confirmé.',
            'transaction_id' => $transactionId,
            'rdv_statut'     => 'Confirme',
        ]);
    }
}
