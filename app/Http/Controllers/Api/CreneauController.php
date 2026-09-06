<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Creneau;
use Illuminate\Http\Request;

class CreneauController extends Controller
{
    /**
     * GET /api/medecin/creneaux
     * Retourne les créneaux du médecin connecté.
     * Query params optionnels : ?from=yyyy-MM-dd&to=yyyy-MM-dd
     *
     * Flutter attend : { data: [ { id, medecin_id, date, heure_debut, heure_fin, disponible } ] }
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'medecin') {
            return response()->json(['message' => 'Accès réservé aux médecins.'], 403);
        }

        $query = Creneau::where('medecin_id', $user->id)
            ->orderBy('date', 'asc')
            ->orderBy('heure_debut', 'asc');

        // Filtre optionnel par plage de dates
        if ($request->filled('from')) {
            $query->where('date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('date', '<=', $request->to);
        }

        $creneaux = $query->get();

        return response()->json([
            'status' => true,
            'data'   => $creneaux
        ]);
    }

    /**
     * POST /api/medecin/creneaux
     * Crée un nouveau créneau de disponibilité pour le médecin connecté.
     * Payload attendu par Flutter :
     *   { date: "yyyy-MM-dd", heure_debut: "HH:mm", heure_fin: "HH:mm", disponible: true }
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'medecin') {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        $validated = $request->validate([
            'date'        => 'required|date|after_or_equal:today',
            'heure_debut' => 'required|string|regex:/^\d{2}:\d{2}$/',
            'heure_fin'   => 'required|string|regex:/^\d{2}:\d{2}$/',
            'disponible'  => 'boolean',
        ]);

        // Vérifier qu'il n'y a pas déjà un créneau identique
        $existe = Creneau::where('medecin_id', $user->id)
            ->where('date', $validated['date'])
            ->where('heure_debut', $validated['heure_debut'])
            ->exists();

        if ($existe) {
            return response()->json([
                'status'  => false,
                'message' => 'Un créneau existe déjà pour cette date et cet horaire.',
            ], 422);
        }

        $creneau = Creneau::create([
            'medecin_id'  => $user->id,
            'date'        => $validated['date'],
            'heure_debut' => $validated['heure_debut'],
            'heure_fin'   => $validated['heure_fin'],
            'disponible'  => $validated['disponible'] ?? true,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Créneau créé avec succès.',
            'data'    => $creneau
        ], 201);
    }

    /**
     * DELETE /api/medecin/creneaux/{id}
     * Supprime un créneau (seulement si non réservé).
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $creneau = Creneau::where('id', $id)
            ->where('medecin_id', $user->id)
            ->firstOrFail();

        $creneau->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Créneau supprimé.'
        ]);
    }
}
