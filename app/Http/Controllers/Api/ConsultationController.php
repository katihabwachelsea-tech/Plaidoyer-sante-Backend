<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConsultationController extends Controller
{
    /**
     * MÉDECIN : Enregistrer la consultation (Diagnostic + Ordonnance)
     */
   public function store(Request $request)
{
    // Flutter envoie 'rendez_vous_id', on accepte aussi 'appointment_id' pour rétro-compatibilité
    if ($request->has('rendez_vous_id') && !$request->has('appointment_id')) {
        $request->merge(['appointment_id' => $request->rendez_vous_id]);
    }

    // 1. Validation des champs obligatoires et optionnels
    $validated = $request->validate([
        'appointment_id' => 'required|exists:appointments,id',
        'diagnostic'     => 'required|string',
        'ordonnance'     => 'nullable|string', // Flutter peut envoyer null
        'notes'          => 'nullable|string', // Flutter envoie 'notes' (pas 'conseils_ia')
        'conseils_ia'    => 'nullable|string',
    ]);

    // 2. Vérifier que le RDV est bien CONFIRMÉ (donc payé)
    $appointment = Appointment::where('id', $validated['appointment_id'])
        ->where('medecin_id', $request->user()->id)
        ->firstOrFail();

    if ($appointment->statut !== 'Confirme') {
        return response()->json([
            'message' => 'Impossible de consulter : le rendez-vous n\'est pas payé ou validé.'
        ], 403);
    }

    // 3. Créer la consultation dans une transaction sécurisée
    return DB::transaction(function () use ($validated, $appointment, $request) {
        $consultation = Consultation::create([
            'appointment_id'    => $validated['appointment_id'],
            'diagnostic'        => $validated['diagnostic'],
            'ordonnance'        => $validated['ordonnance'] ?? $request->ordonnance ?? '',
            // 'notes' de Flutter est stocké dans conseils_ia (champ libre médecin)
            'conseils_ia'       => $request->notes ?? $request->conseils_ia,
            'date_consultation' => now(),
        ]);

        // 4. Marquer le RDV comme "Termine" pour qu'il sorte de la file d'attente
        $appointment->update(['statut' => 'Termine']);

        return response()->json([
            'status'  => true,
            'message' => 'Consultation enregistrée avec succès.',
            'data'    => $consultation
        ]);
    });
}

    /**
     * PATIENT : Voir ses résultats de consultation
     */
    public function show($appointment_id)
    {
        $consultation = Consultation::where('appointment_id', $appointment_id)
            ->with('appointment.medecin.user')
            ->firstOrFail();

        // Sécurité : Vérifier que c'est bien le patient du RDV qui regarde
        if ($consultation->appointment->patient_id !== auth()->id()) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        return response()->json([
            'status' => true,
            'data' => $consultation
        ]);
    }

    public function getMedicalRecord(Request $request)
{
    $patientId = $request->user()->id;

    // On récupère les consultations avec les infos du médecin et du service
    $record = Consultation::whereHas('appointment', function($q) use ($patientId) {
            $q->where('patient_id', $patientId);
        })
        ->with(['appointment.medecin.user', 'appointment.service', 'appointment.invoice'])
        ->orderBy('date_consultation', 'desc')
        ->get();

    return response()->json([
        'status' => true,
        'data' => $record
    ]);
}
}