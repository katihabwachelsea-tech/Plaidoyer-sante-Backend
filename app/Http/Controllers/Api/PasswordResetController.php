<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    /**
     * POST /api/password/forgot
     * Envoie un code OTP à 6 chiffres par email.
     */
    public function sendCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists' => 'Aucun compte n\'est associé à cet email.',
        ]);

        $email = $request->email;

        // Générer un code OTP à 6 chiffres
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Stocker le code (expire dans 15 minutes)
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token'      => Hash::make($code),
                'created_at' => Carbon::now(),
            ]
        );

        // Envoyer l'email
        try {
            Mail::send([], [], function ($message) use ($email, $code) {
                $message->to($email)
                    ->subject('Code de réinitialisation — Mob-Clinic')
                    ->html("
                        <div style='font-family:sans-serif;max-width:480px;margin:0 auto;padding:24px'>
                            <h2 style='color:#107ACA'>Mob-Clinic</h2>
                            <p>Voici votre code de réinitialisation de mot de passe :</p>
                            <div style='font-size:36px;font-weight:bold;letter-spacing:8px;
                                        color:#107ACA;text-align:center;padding:20px;
                                        background:#F0F8FF;border-radius:12px;margin:20px 0'>
                                $code
                            </div>
                            <p style='color:#6B7280;font-size:13px'>
                                Ce code expire dans <strong>15 minutes</strong>.
                                Si vous n'avez pas fait cette demande, ignorez cet email.
                            </p>
                        </div>
                    ");
            });
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Impossible d\'envoyer l\'email. Vérifiez la configuration mail du serveur.',
                'debug'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Un code à 6 chiffres a été envoyé à votre adresse email.',
        ]);
    }

    /**
     * POST /api/password/verify-code
     * Vérifie que le code OTP est valide (sans encore changer le mot de passe).
     * Retourne un token temporaire pour l'étape suivante.
     */
    public function verifyCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code'  => 'required|string|size:6',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return response()->json([
                'status'  => false,
                'message' => 'Aucune demande de réinitialisation trouvée pour cet email.',
            ], 422);
        }

        // Vérifier expiration (15 min)
        if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json([
                'status'  => false,
                'message' => 'Ce code a expiré. Veuillez en demander un nouveau.',
            ], 422);
        }

        if (!Hash::check($request->code, $record->token)) {
            return response()->json([
                'status'  => false,
                'message' => 'Code incorrect. Vérifiez votre email et réessayez.',
            ], 422);
        }

        // Générer un token de session court pour l'étape reset
        $resetToken = Str::random(64);
        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->update(['token' => Hash::make($resetToken)]);

        return response()->json([
            'status'       => true,
            'message'      => 'Code vérifié.',
            'reset_token'  => $resetToken,
            'email'        => $request->email,
        ]);
    }

    /**
     * POST /api/password/reset
     * Réinitialise le mot de passe avec le token obtenu après vérification du code.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'                 => 'required|email|exists:users,email',
            'reset_token'           => 'required|string',
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record || !Hash::check($request->reset_token, $record->token)) {
            return response()->json([
                'status'  => false,
                'message' => 'Token invalide ou expiré. Recommencez la procédure.',
            ], 422);
        }

        // Mettre à jour le mot de passe
        User::where('email', $request->email)
            ->update(['password' => Hash::make($request->password)]);

        // Supprimer le token utilisé
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Mot de passe réinitialisé avec succès. Vous pouvez maintenant vous connecter.',
        ]);
    }
}
