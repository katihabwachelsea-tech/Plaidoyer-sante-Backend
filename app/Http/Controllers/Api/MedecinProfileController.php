<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MedecinProfileController extends Controller
{
    /**
     * GET /api/medecin/profile
     */
    public function show(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'medecin') {
            return response()->json(['message' => 'Accès réservé aux médecins.'], 403);
        }

        return response()->json([
            'status' => true,
            'data'   => $this->formatProfile($user),
        ]);
    }

    /**
     * PUT /api/medecin/profile
     * Payload : { nom?, telephone?, photo_url?, specialite?, hopital?, biographie?, disponibilite? }
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
            'photo_url'     => 'nullable|url|max:500',
            'specialite'    => 'nullable|string|max:100',
            'hopital'       => 'nullable|string|max:150',
            'biographie'    => 'nullable|string',
            'disponibilite' => 'nullable|string',
        ]);

        // Mise à jour table users
        $userFields = array_filter([
            'nom'       => $validated['nom'] ?? null,
            'telephone' => $validated['telephone'] ?? null,
            'photo_url' => $validated['photo_url'] ?? null,
        ], fn($v) => $v !== null);

        if (!empty($userFields)) {
            $user->update($userFields);
        }

        // Mise à jour table medecins
        $medecinFields = array_filter([
            'specialite'    => $validated['specialite'] ?? null,
            'hopital'       => $validated['hopital'] ?? null,
            'biographie'    => $validated['biographie'] ?? null,
            'disponibilite' => $validated['disponibilite'] ?? null,
        ], fn($v) => $v !== null);

        if (!empty($medecinFields)) {
            $user->medecin()->update($medecinFields);
        }

        $user->refresh();
        $user->load('medecin');

        return response()->json([
            'status'  => true,
            'message' => 'Profil mis à jour avec succès.',
            'data'    => $this->formatProfile($user),
        ]);
    }

    /**
     * POST /api/medecin/profile/photo
     * Upload direct d'une photo (multipart/form-data).
     * Stocke dans storage/app/public/medecins/ et retourne l'URL publique.
     */
    public function uploadPhoto(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'medecin') {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        // Suppression de l'ancienne photo si c'est un fichier local
        if ($user->photo_url && str_contains($user->photo_url, '/storage/')) {
            $oldPath = str_replace('/storage/', 'public/', parse_url($user->photo_url, PHP_URL_PATH));
            \Storage::delete($oldPath);
        }

        $path = $request->file('photo')->store('public/medecins');
        $url  = \Storage::url($path); // /storage/medecins/xxx.jpg

        $user->update(['photo_url' => $url]);

        return response()->json([
            'status'    => true,
            'message'   => 'Photo uploadée avec succès.',
            'photo_url' => $url,
        ]);
    }

    // ── Helper ───────────────────────────────────────────────────────────────

    private function formatProfile($user): array
    {
        return [
            'id'            => $user->id,
            'user_id'       => $user->id,
            'nom'           => $user->nom,
            'email'         => $user->email,
            'telephone'     => $user->telephone,
            'photo_url'     => $user->photo_url,
            'specialite'    => $user->medecin->specialite ?? null,
            'licence'       => $user->medecin->licence ?? null,
            'hopital'       => $user->medecin->hopital ?? null,
            'biographie'    => $user->medecin->biographie ?? null,
            'disponibilite' => $user->medecin->disponibilite ?? null,
            'is_validated'  => $user->medecin->is_validated ?? false,
        ];
    }
}
