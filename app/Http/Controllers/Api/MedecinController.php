<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Medecin;

class MedecinController extends Controller
{
    /**
     * Liste tous les médecins validés et complets.
     */
    public function index(Request $request)
    {
        // On récupère les médecins avec les infos de l'utilisateur (nom, tel, etc.)
        $query = Medecin::with('user')
            ->where('is_validated', true) // Uniquement les validés par l'admin
            ->whereHas('user', function($query) {
                $query->where('is_profile_completed', true); // Uniquement ceux qui ont fini l'onboarding
            });

        // Filtre par spécialité
        if ($request->has('specialite') && !empty($request->specialite)) {
            $query->where('specialite', $request->specialite);
        }

        // Recherche par nom, prénom ou hôpital
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('nom', 'like', '%' . $search . '%');
            })->orWhere('hopital', 'like', '%' . $search . '%');
        }

        $medecins = $query->get();

        return response()->json([
            'status' => true,
            'count'  => $medecins->count(),
            'data'   => $medecins
        ]);
    }

    /**
     * Voir les détails d'un médecin spécifique.
     */
    public function show($id)
    {
        $medecin = Medecin::with('user')->find($id);

        if (!$medecin) {
            return response()->json(['message' => 'Médecin non trouvé'], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $medecin
        ]);
    }

    public function update(Request $request)
{
    $user = $request->user();

    // 1. Sécurité : On vérifie que c'est bien un médecin qui appelle cette route
    if ($user->role !== 'medecin') {
        return response()->json(['message' => 'Action non autorisée.'], 403);
    }

    // 2. Validation des données
    $validated = $request->validate([
        'specialite'    => 'nullable|string|max:100',
        'hopital'       => 'nullable|string|max:150',
        'biographie'    => 'nullable|string',
        'disponibilite' => 'nullable|string',
    ]);

    // 3. Mise à jour des infos dans la table 'medecins'
    // On utilise update() sur la relation
    $user->medecin()->update($validated);

    return response()->json([
        'status'  => true,
        'message' => 'Informations professionnelles mises à jour.',
        'data'    => $user->load('medecin')
    ]);
}
    
}
