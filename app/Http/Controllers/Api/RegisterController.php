<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Patient;
use App\Models\Medecin;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        // 1. Règles communes
        $rules = [
            'nom'       => 'required|string|max:255',
            'email'     => 'required|string|email|unique:users',
            'password'  => 'required|string|min:8|confirmed',
            'telephone' => 'required|string',
            'role'      => 'required|in:patient,medecin',
            'photo_url' => 'nullable|url|max:500',
        ];

        // Licence médecin collectée à l'onboarding (complete-profile), pas ici.
        $validated = $request->validate($rules);

        // 3. Création de l'utilisateur
        $user = User::create([
            'nom'       => $validated['nom'],
            'email'     => $validated['email'],
            'password'  => Hash::make($validated['password']),
            'telephone' => $validated['telephone'],
            'role'      => $validated['role'],
            'photo_url' => $validated['photo_url'] ?? null,
        ]);

        // 4. Profil selon le rôle
        if ($user->role === 'patient') {
            Patient::create(['user_id' => $user->id]);
        } elseif ($user->role === 'medecin') {
            Medecin::create(['user_id' => $user->id]);
        }

        $token = $user->createToken('flutter_access_token')->plainTextToken;

        return response()->json([
            'status'  => true,
            'message' => 'Inscription réussie',
            'token'   => $token,
            'user'    => self::formatUser($user),
        ], 201);
    }

    /**
     * Format partagé pour la réponse user — utilisé aussi par AuthController.
     */
    public static function formatUser(User $user): array
    {
        return [
            'id'        => $user->id,
            'nom'       => $user->nom,
            'email'     => $user->email,
            'role'      => $user->role,
            'telephone' => $user->telephone,
            'photo_url' => $user->photo_url,
        ];
    }
}
