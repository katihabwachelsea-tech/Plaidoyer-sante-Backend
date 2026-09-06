<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Patient;
use App\Models\Medecin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
   public function register(Request $request)
{
    // 1. Validation des champs COMMUNS
    $rules = [
        'nom' => 'required|string|max:255',
        'email' => 'required|string|email|unique:users',
        'password' => 'required|string|min:8|confirmed',
        'telephone' => 'required|string',
        'role' => 'required|in:patient,medecin',
    ];

    // 2. Validation SPÉCIFIQUE (uniquement si c'est un médecin)
    if ($request->role === 'medecin') {
        $rules['licence'] = 'required|string|unique:medecins';
    }

    $validatedData = $request->validate($rules);

    // 3. Création de l'User
    $user = User::create([
        'nom' => $validatedData['nom'],
        'email' => $validatedData['email'],
        'password' => Hash::make($validatedData['password']),
        'telephone' => $validatedData['telephone'],
        'role' => $validatedData['role'],
    ]);

    // 4. Création du profil selon le rôle
    if ($user->role === 'patient') {
        Patient::create([
            'user_id' => $user->id,
            // ici tu peux ajouter groupe_sanguin si tu l'as dans le JSON
        ]);
    } elseif ($user->role === 'medecin') {
        Medecin::create([
            'user_id' => $user->id,
            'licence' => $request->licence, // Récupéré seulement pour le médecin
        ]);
    }

    $token = $user->createToken('flutter_access_token')->plainTextToken;

    return response()->json([
        'status'  => true,
        'message' => 'Inscription réussie',
        'token'   => $token,
        'user'    => [
            'id'        => $user->id,
            'nom'       => $user->nom,
            'email'     => $user->email,
            'role'      => $user->role,
            'telephone' => $user->telephone,
        ]
    ], 201);
}
}