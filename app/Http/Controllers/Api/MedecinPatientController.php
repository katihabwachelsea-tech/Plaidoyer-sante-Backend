<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedecinPatient;
use Illuminate\Http\Request;

class MedecinPatientController extends Controller
{
    /**
     * GET /api/medecin/patients
     * Liste les patients créés/suivis par le médecin connecté.
     * Flutter attend : { data: [ { id, server_id, nom, prenom, age, pays, maladie, conseils,
     *                              date_creation, derniere_visite } ] }
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'medecin') {
            return response()->json(['message' => 'Accès réservé aux médecins.'], 403);
        }

        $patients = MedecinPatient::where('medecin_id', $user->id)
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $patients
        ]);
    }

    /**
     * POST /api/medecin/patients
     * Crée un nouveau patient dans le carnet du médecin.
     * Payload Flutter (Patient.toApiMap()) :
     *   { nom, prenom, age, pays, maladie, conseils, date_creation, derniere_visite? }
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'medecin') {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        $validated = $request->validate([
            'nom'             => 'required|string|max:100',
            'prenom'          => 'required|string|max:100',
            'age'             => 'required|integer|min:0|max:130',
            'pays'            => 'required|string|max:100',
            'maladie'         => 'nullable|string|max:255',
            'conseils'        => 'nullable|string',
            'date_creation'   => 'nullable|date',
            'derniere_visite' => 'nullable|date',
        ]);

        $patient = MedecinPatient::create([
            'medecin_id'      => $user->id,
            'nom'             => $validated['nom'],
            'prenom'          => $validated['prenom'],
            'age'             => $validated['age'],
            'pays'            => $validated['pays'],
            'maladie'         => $validated['maladie'] ?? null,
            'conseils'        => $validated['conseils'] ?? null,
            'date_creation'   => $validated['date_creation'] ?? now()->toDateString(),
            'derniere_visite' => $validated['derniere_visite'] ?? null,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Patient enregistré avec succès.',
            'data'    => $patient
        ], 201);
    }

    /**
     * PUT /api/medecin/patients/{id}
     * Met à jour un patient du carnet du médecin.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();

        $patient = MedecinPatient::where('id', $id)
            ->where('medecin_id', $user->id)
            ->firstOrFail();

        $validated = $request->validate([
            'nom'             => 'nullable|string|max:100',
            'prenom'          => 'nullable|string|max:100',
            'age'             => 'nullable|integer|min:0|max:130',
            'pays'            => 'nullable|string|max:100',
            'maladie'         => 'nullable|string|max:255',
            'conseils'        => 'nullable|string',
            'date_creation'   => 'nullable|date',
            'derniere_visite' => 'nullable|date',
        ]);

        $patient->update(array_filter($validated, fn($v) => $v !== null));

        return response()->json([
            'status'  => true,
            'message' => 'Patient mis à jour avec succès.',
            'data'    => $patient->fresh()
        ]);
    }

    /**
     * DELETE /api/medecin/patients/{id}
     * Supprime un patient du carnet du médecin.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $patient = MedecinPatient::where('id', $id)
            ->where('medecin_id', $user->id)
            ->firstOrFail();

        $patient->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Patient supprimé.'
        ]);
    }
}
