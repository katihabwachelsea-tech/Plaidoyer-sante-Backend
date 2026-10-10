<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Medecin;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminWebController extends Controller
{
    public function showLogin()
    {
        if (Auth::check() && Auth::user()->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials)) {
            return back()->withErrors(['email' => 'Identifiants invalides.'])->onlyInput('email');
        }

        if (Auth::user()->role !== 'admin') {
            Auth::logout();
            return back()->withErrors(['email' => 'Accès réservé à l’administrateur.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    public function dashboard()
    {
        $this->ensureAdmin();
        $pending = Medecin::with('user')->where('is_validated', false)->orderByDesc('created_at')->get();
        $validated = Medecin::with('user')->where('is_validated', true)->orderBy('specialite')->get();
        $services = Service::orderBy('nom_service')->get();

        $stats = [
            'medecins_valides' => $validated->count(),
            'medecins_attente' => $pending->count(),
            'rdv' => Appointment::count(),
            'rdv_confirmes' => Appointment::where('statut', 'Confirme')->count(),
            'paiements' => Invoice::where('statut_paiement', 'Paye')->count(),
            'patients' => User::where('role', 'patient')->count(),
        ];

        return view('admin.dashboard', compact('pending', 'validated', 'services', 'stats'));
    }

    public function validateMedecin(Request $request, $id)
    {
        $this->ensureAdmin();
        $medecin = Medecin::with('user')->where('user_id', $id)->firstOrFail();

        if (empty($medecin->licence) || !preg_match('/^MED-[A-Z0-9]+$/i', $medecin->licence)) {
            return back()->with('error', 'Licence absente ou invalide (format attendu : MED-…).');
        }

        $medecin->update(['is_validated' => true]);

        return back()->with('success', 'Médecin validé : '.$medecin->user?->nom);
    }

    public function updateService(Request $request, $id)
    {
        $this->ensureAdmin();
        $data = $request->validate([
            'prix' => 'required|numeric|min:0',
        ]);

        $service = Service::findOrFail($id);
        $service->update(['prix' => $data['prix']]);

        return back()->with('success', 'Tarif mis à jour : '.$service->nom_service);
    }

    private function ensureAdmin(): void
    {
        abort_unless(Auth::check() && Auth::user()->role === 'admin', 403);
    }
}
