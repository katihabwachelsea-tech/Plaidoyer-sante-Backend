<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * POST /api/complete-profile          (route générique)
     * POST /api/medecin/complete-profile  (route dédiée médecin — appelée par Flutter AuthService)
     * POST /api/patient/complete-profile  (route dédiée patient — appelée par Flutter AuthService)
     *
     * Les trois routes pointent vers cette méthode.
     * Le rôle de l'utilisateur connecté fait foi.
     *
     * Payload médecin  : { specialite, licence, hopital, biographie?, disponibilite? }
     * Payload patient  : { date_naissance?, groupe_sanguin?, maladie?, antecedents? }
     */
    public function completeProfile(Request $request)
    {
        $user = $request->user();

        // --- CAS DU MÉDECIN ---
        if ($user->role === 'medecin') {
            $request->validate([
                'specialite'    => 'required|string|max:100',
                'licence'       => 'required|string|unique:medecins,licence,' . ($user->medecin->id ?? 'NULL'),
                'hopital'       => 'required|string|max:150',
                'biographie'    => 'nullable|string',
                'disponibilite' => 'nullable|string',
            ]);

            $user->medecin()->update([
                'specialite'    => $request->specialite,
                'licence'       => $request->licence,
                'hopital'       => $request->hopital,
                'biographie'    => $request->biographie,
                'disponibilite' => $request->disponibilite,
            ]);
        }

        // --- CAS DU PATIENT ---
        elseif ($user->role === 'patient') {
            $request->validate([
                'date_naissance' => 'nullable|date',
                'groupe_sanguin' => 'nullable|string|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
                'maladie'        => 'nullable|string',
                'antecedents'    => 'nullable|string',
                'allergies'      => 'nullable|string',
            ]);

            $user->patient()->update([
                'date_naissance' => $request->date_naissance,
                'groupe_sanguin' => $request->groupe_sanguin,
                'maladie'        => $request->maladie,
                'antecedents'    => $request->antecedents,
                'allergies'      => $request->allergies,
            ]);
        } else {
            return response()->json(['message' => 'Rôle non supporté pour cette action.'], 403);
        }

        // Onboarding terminé pour les deux rôles
        $user->update(['is_profile_completed' => true]);

        return response()->json([
            'status'  => true,
            'message' => 'Profil ' . $user->role . ' complété avec succès.',
            'user'    => $user->load($user->role),
        ]);
    }

    public function me(Request $request)
    {
        // Retourne l'utilisateur avec toutes ses relations pour que Flutter sache tout
        return response()->json($request->user()->load(['patient', 'medecin']));
    }

    public function getAllUsers()
{
    // On récupère TOUS les utilisateurs avec leurs détails Patient ou Medecin
    $users = \App\Models\User::with(['patient', 'medecin'])->get();

    return response()->json([
        'status' => true,
        'data' => $users
    ]);
}
}