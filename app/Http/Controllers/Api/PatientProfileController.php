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
            'allergies'      => 'nullable|string',
        ]);

        $patient = Patient::where('user_id', $user->id)->firstOrFail();

        $patient->update(array_filter([
            'date_naissance' => $validated['date_naissance'] ?? null,
            'groupe_sanguin' => $validated['groupe_sanguin'] ?? null,
            'maladie'        => $validated['maladie'] ?? null,
            'antecedents'    => $validated['antecedents'] ?? null,
            'allergies'      => $validated['allergies'] ?? null,
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

    /**
     * POST /api/patient/profile/photo
     */
    public function uploadPhoto(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'patient') {
            return response()->json(['status' => false, 'message' => 'Accès réservé aux patients.'], 403);
        }

        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        if ($user->photo_url && str_contains($user->photo_url, '/storage/')) {
            $oldPath = str_replace('/storage/', 'public/', parse_url($user->photo_url, PHP_URL_PATH));
            \Storage::delete($oldPath);
        }

        $path = $request->file('photo')->store('public/patients');
        $url = \Storage::url($path);

        $user->update(['photo_url' => $url]);

        return response()->json([
            'status' => true,
            'message' => 'Photo uploadée avec succès.',
            'photo_url' => $url,
        ]);
    }

    private function format(Patient $patient): array
    {
        return [
            'user_id'        => $patient->user_id,
            'nom'            => $patient->user?->nom,
            'email'          => $patient->user?->email,
            'telephone'      => $patient->user?->telephone,
            'photo_url'      => $patient->user?->photo_url,
            'date_naissance' => $patient->date_naissance,
            'groupe_sanguin' => $patient->groupe_sanguin,
            'maladie'        => $patient->maladie,
            'antecedents'    => $patient->antecedents,
            'allergies'      => $patient->allergies,
        ];
    }
}
