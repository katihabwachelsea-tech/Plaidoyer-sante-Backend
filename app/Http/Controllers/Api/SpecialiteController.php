<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medecin;

class SpecialiteController extends Controller
{
    /**
     * GET /api/specialites
     * Retourne la liste des spécialités distinctes parmi les médecins validés.
     * Flutter attend : { data: ["Cardiologie", "Pédiatrie", ...] }
     */
    public function index()
    {
        $specialites = Medecin::where('is_validated', true)
            ->whereNotNull('specialite')
            ->distinct()
            ->orderBy('specialite')
            ->pluck('specialite');

        return response()->json([
            'status' => true,
            'data'   => $specialites
        ]);
    }
}
