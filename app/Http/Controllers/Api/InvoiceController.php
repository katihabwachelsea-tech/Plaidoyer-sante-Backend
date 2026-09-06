<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    /**
     * PATIENT : Payer la consultation (Simulation Ecocash/Lumicash)
     */
    public function pay(Request $request, $id)
    {
        // 1. Trouver la facture ou erreur 404
        $invoice = Invoice::with('appointment')->findOrFail($id);

        // 2. Vérifier si elle n'est pas déjà payée
        if ($invoice->statut_paiement === 'Paye') {
            return response()->json(['message' => 'Cette facture est déjà réglée.'], 422);
        }

        // 3. Vérifier si le rendez-vous n'est pas déjà expiré/annulé
        if ($invoice->appointment->statut === 'Annule' || $invoice->appointment->date_rdv <= now()) {
            return response()->json([
                'message' => 'Désolé, ce créneau a expiré ou a été annulé. Vous ne pouvez plus le payer.'
            ], 422);
        }

        // 4. Valider les infos de paiement
        $validated = $request->validate([
            'methode_paiement' => 'required|in:Ecocash,Lumicash',
            'telephone'        => 'required|string|min:8', 
        ]);

        // 5. Simulation du Transaction ID
        $transactionId = strtoupper($validated['methode_paiement']) . "-" . now()->format('YmdHis') . "-" . rand(100, 999);

        // 6. TRANSACTION SQL : On met à jour la Facture ET le Rendez-vous
        return DB::transaction(function () use ($invoice, $validated, $transactionId) {
            
            // Mise à jour de la facture
            $invoice->update([
                'statut_paiement'  => 'Paye',
                'methode_paiement' => $validated['methode_paiement'],
                'transaction_id'   => $transactionId,
            ]);

            // Mise à jour du rendez-vous -> DEVIENT VISIBLE POUR LE MÉDECIN
            $invoice->appointment->update([
                'statut' => 'Confirme'
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Paiement réussi ! Votre rendez-vous est maintenant confirmé.',
                'transaction_id' => $transactionId,
                'rdv_statut' => 'Confirme'
            ]);
        });
    }
}