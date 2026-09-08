<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;

class PatientProfileController extends Controller
{
    /**
     * GET /api/patient/profile
     */
    public function show(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'patient') {
            return response()->json(['status' => false, 'message' => 'Accès réservé aux patients.'], 403);
        }

        $patient = Patient::with('user')->where('user_id', $user->id)->first();
        if (!$patient) {
            return response()->json(['status' => false, 'message' => 'Profil patient introuvable.'], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $this->format($patient),
        ]);
    }

    /**
     * PUT /api/patient/profile
     */
    public function update(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'patient') {
            return response()->json(['status' => false, 'message' => 'Accès réservé aux patients.'], 403);
        }

        $validated = $request->validate([
            'nom'            => 'nullable|string|max:120',
            'telephone'      => 'nullable|string|max:30',
            'date_naissance' => 'nullable|date',
            'groupe_sanguin' => 'nullable|string|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'maladie'        => 'nullable|string|max:255',
            'antecedents'    => 'nullable|string',
        ]);

        $patient = Patient::where('user_id', $user->id)->firstOrFail();

        $patient->update(array_filter([
            'date_naissance' => $validated['date_naissance'] ?? null,
            'groupe_sanguin' => $validated['groupe_sanguin'] ?? null,
            'maladie'        => $validated['maladie'] ?? null,
            'antecedents'    => $validated['antecedents'] ?? null,
        ], fn ($v) => $v !== null));

        $userUpdates = array_filter([
            'nom'       => $validated['nom'] ?? null,
            'telephone' => $validated['telephone'] ?? null,
        ], fn ($v) => $v !== null);

        if ($userUpdates) {
            $user->update($userUpdates);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Profil mis à jour.',
            'data'    => $this->format($patient->fresh('user')),
        ]);
    }

    private function format(Patient $patient): array
    {
        return [
            'user_id'        => $patient->user_id,
            'nom'            => $patient->user?->nom,
            'email'          => $patient->user?->email,
            'telephone'      => $patient->user?->telephone,
            'date_naissance' => $patient->date_naissance,
            'groupe_sanguin' => $patient->groupe_sanguin,
            'maladie'        => $patient->maladie,
            'antecedents'    => $patient->antecedents,
        ];
    }
}
