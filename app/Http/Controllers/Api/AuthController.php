<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Connexion de l'utilisateur et génération du Token
     */
    public function login(Request $request)
    {
        // 1. Validation des données entrantes
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // 2. Recherche de l'utilisateur par son email
        $user = User::where('email', $request->email)->first();
       // return response()->json(['saisi' => $request->password, 'en_base' => $user->password]);

        // 3. Vérification du mot de passe (Hash::check compare le clair avec le haché)
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Identifiants invalides.',
            ], 401);
        }

        // 4. Création du token Sanctum
        // On donne un nom au token (ex: 'flutter_access_token')
        $token = $user->createToken('flutter_access_token')->plainTextToken;

        // 5. Réponse JSON pour Flutter
        return response()->json([
            'status' => true,
            'message' => 'Utilisateur connecté avec succès',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'nom' => $user->nom,
                'email' => $user->email,
                'role' => $user->role,
                'telephone' => $user->telephone
            ]
        ], 200);
    }

    /**
     * Déconnexion (Révocation du token)
     */
    public function logout(Request $request)
    {
        // Supprime le token qui a servi à authentifier cette requête
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Déconnecté avec succès, token supprimé.'
        ]);
    }
}
