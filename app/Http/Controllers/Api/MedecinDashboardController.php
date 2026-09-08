<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Creneau;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MedecinDashboardController extends Controller
{
    /**
     * GET /api/medecin/dashboard
     * Indicateurs réels du cabinet, sans données fictives.
     */
    public function show(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'medecin') {
            return response()->json(['status' => false, 'message' => 'Accès réservé aux médecins.'], 403);
        }

        $medecinId = $user->id;
        $today = Carbon::today();
        $weekEnd = Carbon::today()->addDays(7);

        $todayCount = Appointment::where('medecin_id', $medecinId)
            ->where('statut', 'Confirme')
            ->whereDate('date_rdv', $today)
            ->count();

        $upcomingCount = Appointment::where('medecin_id', $medecinId)
            ->where('statut', 'Confirme')
            ->where('date_rdv', '>=', now())
            ->count();

        $patientsCount = Appointment::where('medecin_id', $medecinId)
            ->whereIn('statut', ['Confirme', 'Termine'])
            ->distinct('patient_id')
            ->count('patient_id');

        $creneauxCount = Creneau::where('medecin_id', $medecinId)
            ->whereDate('date', '>=', $today->toDateString())
            ->whereDate('date', '<=', $weekEnd->toDateString())
            ->where('disponible', true)
            ->count();

        $consultationsCount = Consultation::whereHas('appointment', function ($q) use ($medecinId) {
            $q->where('medecin_id', $medecinId);
        })->count();

        $next = Appointment::with('patient.user')
            ->where('medecin_id', $medecinId)
            ->where('statut', 'Confirme')
            ->where('date_rdv', '>=', now())
            ->orderBy('date_rdv')
            ->first();

        return response()->json([
            'status' => true,
            'data'   => [
                'nom'                 => $user->nom,
                'today_count'         => $todayCount,
                'upcoming_count'      => $upcomingCount,
                'patients_count'      => $patientsCount,
                'creneaux_week_count' => $creneauxCount,
                'consultations_count' => $consultationsCount,
                'next_appointment'    => $next ? [
                    'id'          => $next->id,
                    'date_rdv'    => $next->date_rdv?->format('Y-m-d H:i:s'),
                    'motif'       => $next->motif,
                    'patient_nom' => $next->patient?->user?->nom,
                ] : null,
            ],
        ]);
    }
}
