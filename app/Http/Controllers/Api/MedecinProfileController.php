<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MedecinProfileController extends Controller
{
    /**
     * GET /api/medecin/profile
     * Retourne le profil complet du médecin connecté (infos user + infos medecin).
     */
    public function show(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'medecin') {
            return response()->json(['message' => 'Accès réservé aux médecins.'], 403);
        }

        return response()->json([
            'status' => true,
            'data'   => [
                'id'            => $user->medecin->user_id ?? null,
                'user_id'       => $user->id,
                'nom'           => $user->nom,
                'email'         => $user->email,
                'telephone'     => $user->telephone,
                'specialite'    => $user->medecin->specialite ?? null,
                'licence'       => $user->medecin->licence ?? null,
                'hopital'       => $user->medecin->hopital ?? null,
                'biographie'    => $user->medecin->biographie ?? null,
                'disponibilite' => $user->medecin->disponibilite ?? null,
                'is_validated'  => $user->medecin->is_validated ?? false,
            ]
        ]);
    }

    /**
     * PUT /api/medecin/profile
     * Met à jour les informations du profil médecin (table users + table medecins).
     * Payload attendu par Flutter :
     *   { nom?, telephone?, specialite, hopital, biographie?, disponibilite? }
     */
    public function update(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'medecin') {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        $validated = $request->validate([
            'nom'           => 'nullable|string|max:255',
            'telephone'     => 'nullable|string|max:30',
            'specialite'    => 'nullable|string|max:100',
            'hopital'       => 'nullable|string|max:150',
            'biographie'    => 'nullable|string',
            'disponibilite' => 'nullable|string',
        ]);

        // Mise à jour de la table users (nom, telephone)
        $userFields = array_filter([
            'nom'       => $validated['nom'] ?? null,
            'telephone' => $validated['telephone'] ?? null,
        ], fn($v) => $v !== null);

        if (!empty($userFields)) {
            $user->update($userFields);
        }

        // Mise à jour de la table medecins
        $medecinFields = array_filter([
            'specialite'    => $validated['specialite'] ?? null,
            'hopital'       => $validated['hopital'] ?? null,
            'biographie'    => $validated['biographie'] ?? null,
            'disponibilite' => $validated['disponibilite'] ?? null,
        ], fn($v) => $v !== null);

        if (!empty($medecinFields)) {
            $user->medecin()->update($medecinFields);
        }

        // Rechargement de l'utilisateur avec ses relations
        $user->refresh();
        $user->load('medecin');

        return response()->json([
            'status'  => true,
            'message' => 'Profil mis à jour avec succès.',
            'data'    => [
                'id'            => $user->medecin->user_id ?? null,
                'user_id'       => $user->id,
                'nom'           => $user->nom,
                'email'         => $user->email,
                'telephone'     => $user->telephone,
                'specialite'    => $user->medecin->specialite ?? null,
                'licence'       => $user->medecin->licence ?? null,
                'hopital'       => $user->medecin->hopital ?? null,
                'biographie'    => $user->medecin->biographie ?? null,
                'disponibilite' => $user->medecin->disponibilite ?? null,
                'is_validated'  => $user->medecin->is_validated ?? false,
            ]
        ]);
    }
}
