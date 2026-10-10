<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\Appointment;
use App\Models\AppNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConsultationController extends Controller
{
    /**
     * MÉDECIN : Enregistrer la consultation (Diagnostic + Ordonnance)
     */
    public function store(Request $request)
    {
        if ($request->has('rendez_vous_id') && !$request->has('appointment_id')) {
            $request->merge(['appointment_id' => $request->rendez_vous_id]);
        }

        $validated = $request->validate([
            'appointment_id'         => 'required|exists:appointments,id',
            'diagnostic'             => 'required|string|min:1',
            'ordonnance'             => 'required|string|min:1',
            'notes'                  => 'nullable|string',
            'conseils_ia'            => 'nullable|string',
            'anamnese'               => 'nullable|string',
            'examen'                 => 'nullable|string',
            'signature'              => 'required|string',
            // SOAP
            'soap_s'                 => 'nullable|string',
            'soap_o'                 => 'nullable|string',
            'soap_a'                 => 'nullable|string',
            'soap_p'                 => 'nullable|string',
            // Constantes vitales & médicaments (JSON envoyé encodé)
            'constantes_vitales'     => 'nullable|array',
            'medicaments'            => 'nullable|array',
            'examens_complementaires'=> 'nullable|array',
            // V.10.10/V.10.11 : saisie offline le jour J, push différé
            'offline_recorded_at'    => 'nullable|date',
        ]);

        $appointment = Appointment::where('id', $validated['appointment_id'])
            ->where('medecin_id', $request->user()->id)
            ->firstOrFail();

        // Idempotence sync offline : déjà consulté → OK
        $existing = Consultation::where('appointment_id', $validated['appointment_id'])->first();
        if ($existing) {
            return response()->json([
                'status'  => true,
                'message' => 'Consultation déjà enregistrée.',
                'data'    => $existing,
            ]);
        }

        if ($appointment->statut !== 'Confirme') {
            return response()->json([
                'message' => 'Impossible de consulter : le rendez-vous n\'est pas payé ou validé.',
            ], 403);
        }

        // Gate jour J — ou saisie offline enregistrée le jour du RDV
        $rdvDay = Carbon::parse($appointment->date_rdv)->startOfDay();
        $today = Carbon::today();
        $offlineAt = isset($validated['offline_recorded_at'])
            ? Carbon::parse($validated['offline_recorded_at'])->startOfDay()
            : null;

        $allowedByOffline = $offlineAt !== null && $offlineAt->equalTo($rdvDay);
        if (!$rdvDay->equalTo($today) && !$allowedByOffline) {
            $label = Carbon::parse($appointment->date_rdv)->locale('fr')->isoFormat('dddd D MMMM YYYY');
            return response()->json([
                'message' => "Consultation autorisée uniquement le jour du rendez-vous ($label).",
                'code' => 'NOT_APPOINTMENT_DAY',
                'date_rdv' => $appointment->date_rdv?->toIso8601String(),
            ], 403);
        }

        return DB::transaction(function () use ($validated, $appointment, $request) {
            $signaturePath = null;
            if (!empty($validated['signature'])) {
                $raw = $validated['signature'];
                if (str_starts_with($raw, 'data:image')) {
                    $raw = substr($raw, strpos($raw, ',') + 1);
                }
                $bytes = base64_decode($raw, true);
                if ($bytes !== false) {
                    $filename = 'signatures/consult_'.$appointment->id.'_'.time().'.png';
                    \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $bytes);
                    $signaturePath = '/storage/'.$filename;
                }
            }

            $consultation = Consultation::create([
                'appointment_id'          => $validated['appointment_id'],
                'anamnese'                => $validated['anamnese'] ?? null,
                'examen'                  => $validated['examen'] ?? null,
                'diagnostic'              => $validated['diagnostic'],
                'ordonnance'              => $validated['ordonnance'],
                'conseils_ia'             => $validated['notes'] ?? $validated['conseils_ia'] ?? null,
                'signature_path'          => $signaturePath,
                'date_consultation'       => now(),
                // SOAP
                'soap_s'                  => $validated['soap_s'] ?? null,
                'soap_o'                  => $validated['soap_o'] ?? null,
                'soap_a'                  => $validated['soap_a'] ?? null,
                'soap_p'                  => $validated['soap_p'] ?? null,
                // Données structurées JSON
                'constantes_vitales'      => $validated['constantes_vitales'] ?? null,
                'medicaments'             => $validated['medicaments'] ?? null,
                'examens_complementaires' => $validated['examens_complementaires'] ?? null,
            ]);

            $appointment->update(['statut' => 'Termine']);

            AppNotification::notify(
                (int) $appointment->patient_id,
                'Résultats disponibles',
                'Votre ordonnance et le compte rendu de consultation sont disponibles dans votre dossier médical.',
                'consultation_done',
                ['appointment_id' => $appointment->id]
            );

            return response()->json([
                'status'  => true,
                'message' => 'Consultation enregistrée avec succès.',
                'data'    => $consultation,
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

        if ($consultation->appointment->patient_id !== auth()->id()) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        return response()->json([
            'status' => true,
            'data' => $consultation,
        ]);
    }

    public function getMedicalRecord(Request $request)
    {
        $patientId = $request->user()->id;

        $record = Consultation::whereHas('appointment', function ($q) use ($patientId) {
            $q->where('patient_id', $patientId);
        })
            ->with(['appointment.medecin.user', 'appointment.service', 'appointment.invoice'])
            ->orderBy('date_consultation', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $record,
        ]);
    }
}
