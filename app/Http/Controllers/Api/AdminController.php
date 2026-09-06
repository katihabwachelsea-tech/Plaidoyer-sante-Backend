<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medecin;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Valider ou suspendre un médecin (Action réservée à l'Admin).
     */
    public function validateMedecin(Request $request, $id)
 {
    // 1. Vérifier le rôle Admin
    if ($request->user()->role !== 'admin') {
        return response()->json(['message' => 'Accès interdit.'], 403);
    }

    // 2. Trouver le médecin
    $medecin = Medecin::find($id);

    if (!$medecin) {
        return response()->json(['message' => 'Médecin non trouvé.'], 404);
    }

    // 3. VÉRIFICATION DE LA LICENCE
    // On vérifie si elle est vide
    if (empty($medecin->licence)) {
        return response()->json([
            'status' => false,
            'message' => 'Validation impossible : Le médecin n\'a pas renseigné sa licence.'
        ], 422);
    }

    // 4. VÉRIFICATION DU FORMAT (TYPE)
    // Exemple de format : "MED-" suivi de chiffres ou lettres (ajuste selon le standard au Burundi)
    // Ici : /^MED-[0-9]{4,8}$/ signifie "Commence par MED-, suivi de 4 à 8 chiffres"
    $pattern = "/^MED-[A-Z0-9]+$/i"; 
    
    if (!preg_match($pattern, $medecin->licence)) {
        return response()->json([
            'status' => false,
            'message' => 'Le format de la licence ('.$medecin->licence.') est invalide pour le système.'
        ], 422);
    }

    // 5. Tout est bon, on valide
    $medecin->update(['is_validated' => true]);

    return response()->json([
        'status' => true,
        'message' => 'Médecin certifié et validé avec succès.',
        'data' => $medecin->load('user')
    ]);
 }
/**
     * Liste de tous les médecins en attente de validation.
     */
    public function getPendingMedecins(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $pending = Medecin::with('user')->where('is_validated', false)->get();

        return response()->json([
            'status' => true,
            'data'   => $pending
        ]);
    }
}