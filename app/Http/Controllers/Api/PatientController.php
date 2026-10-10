<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    /**
     * GET /api/patients/{id}
     *
     * Règle de sécurité :
     *   - Un patient connecté ne peut voir QUE son propre profil.
     *   - Un médecin connecté peut voir le profil de n'importe quel patient
     *     (il en a besoin pour la consultation).
     *   - L'admin peut tout voir.
     *
     * Le paramètre {id} dans la route est l'ID de l'utilisateur (users.id),
     * pas l'ID de la table patients (qui utilise user_id comme PK).
     */
    public function show(Request $request, $id)
    {
        $authUser = $request->user();

        // Sécurité : un patient ne peut voir que lui-même
        if ($authUser->role === 'patient' && $authUser->id != $id) {
            return response()->json([
                'status'  => false,
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        // Recherche du profil patient via user_id (PK non standard)
        $patient = Patient::with('user')->where('user_id', $id)->first();

        if (!$patient) {
            return response()->json([
                'status'  => false,
                'message' => 'Profil patient introuvable.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => [
                'user_id'        => $patient->user_id,
                'nom'            => $patient->user?->nom,
                'email'          => $patient->user?->email,
                'telephone'      => $patient->user?->telephone,
                'date_naissance' => $patient->date_naissance,
                'groupe_sanguin' => $patient->groupe_sanguin,
                'maladie'        => $patient->maladie,
                'antecedents'    => $patient->antecedents,
            ],
        ]);
    }

    /**
     * GET /api/medecin/patients/{patient_id}/dossier
     *
     * Dossier complet d'un patient — réservé aux médecins.
     * Retourne : profil, constantes vitales (dernière consult), traitements
     * en cours (médicaments de toutes les consultations), historique.
     */
    public function dossier(Request $request, $patientId)
    {
        $authUser = $request->user();

        if ($authUser->role !== 'medecin') {
            return response()->json(['status' => false, 'message' => 'Accès réservé aux médecins.'], 403);
        }

        $patient = Patient::with('user')->where('user_id', $patientId)->first();
        if (!$patient) {
            return response()->json(['status' => false, 'message' => 'Patient introuvable.'], 404);
        }

        // Toutes les consultations de ce patient (ordonnées par date desc)
        $consultations = \App\Models\Consultation::whereHas('appointment', function ($q) use ($patientId) {
            $q->where('patient_id', $patientId);
        })
        ->with(['appointment.medecin.user', 'appointment.service'])
        ->orderBy('date_consultation', 'desc')
        ->get();

        // Dernières constantes vitales (première consultation qui en a)
        $lastConstantes = null;
        $lastConsultDate = null;
        foreach ($consultations as $c) {
            if (!empty($c->constantes_vitales)) {
                $lastConstantes  = $c->constantes_vitales;
                $lastConsultDate = $c->date_consultation;
                break;
            }
        }

        // Tous les médicaments de toutes les consultations (traitements)
        $medicaments = [];
        foreach ($consultations as $c) {
            if (!empty($c->medicaments)) {
                foreach ($c->medicaments as $m) {
                    $medicaments[] = array_merge($m, [
                        'prescrit_le' => optional($c->date_consultation)->toDateString(),
                        'consultation_id' => $c->id,
                    ]);
                }
            }
        }

        // Historique consultations formaté
        $historique = $consultations->map(function ($c) {
            return [
                'id'                      => $c->id,
                'appointment_id'          => $c->appointment_id,
                'date_consultation'       => optional($c->date_consultation)->toIso8601String(),
                'diagnostic'              => $c->diagnostic,
                'ordonnance'              => $c->ordonnance,
                'anamnese'                => $c->anamnese,
                'examen'                  => $c->examen,
                'notes'                   => $c->conseils_ia,
                'conseils_ia'             => $c->conseils_ia,
                'soap_s'                  => $c->soap_s,
                'soap_o'                  => $c->soap_o,
                'soap_a'                  => $c->soap_a,
                'soap_p'                  => $c->soap_p,
                'constantes_vitales'      => $c->constantes_vitales,
                'medicaments'             => $c->medicaments,
                'examens_complementaires' => $c->examens_complementaires,
                'signature_path'          => $c->signature_path,
                'medecin'                 => $c->appointment?->medecin?->user?->nom,
                'service'                 => $c->appointment?->service?->nom_service,
                'motif'                   => $c->appointment?->motif,
            ];
        });

        return response()->json([
            'status' => true,
            'data'   => [
                'profil' => [
                    'user_id'        => $patient->user_id,
                    'nom'            => $patient->user?->nom,
                    'email'          => $patient->user?->email,
                    'telephone'      => $patient->user?->telephone,
                    'date_naissance' => $patient->date_naissance,
                    'groupe_sanguin' => $patient->groupe_sanguin,
                    'maladie'        => $patient->maladie,
                    'antecedents'    => $patient->antecedents,
                ],
                'dernieres_constantes' => $lastConstantes,
                'derniere_consult_date' => optional($lastConsultDate)->toIso8601String(),
                'medicaments'          => $medicaments,
                'historique'           => $historique,
            ],
        ]);
    }

    /**
     * Met à jour les informations médicales du patient identifié par {id}.
     * Seul le patient lui-même peut modifier son profil.
     */
    public function update(Request $request, $id)
    {
        $authUser = $request->user();

        // Seul le patient concerné peut modifier son propre profil
        if ($authUser->id != $id) {
            return response()->json([
                'status'  => false,
                'message' => 'Action non autorisée.',
            ], 403);
        }

        if ($authUser->role !== 'patient') {
            return response()->json([
                'status'  => false,
                'message' => 'Action réservée aux patients.',
            ], 403);
        }

        $validated = $request->validate([
            'date_naissance' => 'nullable|date',
            'groupe_sanguin' => 'nullable|string|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'maladie'        => 'nullable|string|max:255',
            'antecedents'    => 'nullable|string',
        ]);

        $patient = Patient::where('user_id', $id)->firstOrFail();

        // Filtrer les nulls pour ne mettre à jour que les champs fournis
        $patient->update(array_filter($validated, fn($v) => $v !== null));

        return response()->json([
            'status'  => true,
            'message' => 'Informations médicales mises à jour avec succès.',
            'data'    => $patient->fresh()->load('user'),
        ]);
    }
}
