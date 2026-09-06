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
     * PUT /api/patients/{id}
     *
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
