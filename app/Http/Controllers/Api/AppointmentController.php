<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppNotification;
use App\Models\Creneau;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\Medecin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    /**
     * PATIENT : Réserver un créneau (Automatisé)
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'medecin_id' => 'required|exists:medecins,user_id',
            'service_id' => 'required|exists:services,id',
            'date_rdv'   => 'required|date|after:now', // Format: Y-m-d H:i:s
            'motif'      => 'required|string',
        ]);

        // 1. Vérification de la collision (Cadenas)
        // Un créneau est indisponible s'il y a un RDV "Confirme" 
        // OU un RDV "En_attente" dont l'heure n'est pas encore passée.
        $dejaPris = Appointment::where('medecin_id', $validated['medecin_id'])
            ->where('date_rdv', $validated['date_rdv'])
            ->where(function ($query) {
                $query->where('statut', 'Confirme')
                      ->orWhere(function ($q) {
                          $q->where('statut', 'En_attente')
                            ->where('date_rdv', '>', now()); 
                      });
            })
            ->exists();

        if ($dejaPris) {
            return response()->json([
                'status' => false,
                'message' => 'Ce créneau est déjà réservé ou en attente de paiement.'
            ], 422);
        }

        // 2. Récupérer le service pour le prix
        $service = Service::findOrFail($validated['service_id']);

        // 3. Création automatique RDV + Facture
        return DB::transaction(function () use ($validated, $user, $service) {
            
            $appointment = Appointment::create([
                'patient_id' => $user->id,
                'medecin_id' => $validated['medecin_id'],
                'service_id' => $validated['service_id'],
                'date_rdv'   => $validated['date_rdv'],
                'motif'      => $validated['motif'],
                'statut'     => 'En_attente', // Statut initial
            ]);

            Invoice::create([
                'appointment_id' => $appointment->id,
                'montant'        => $service->prix,
                'statut_paiement'=> 'Non_paye',
                'date_emission'  => now(),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Créneau réservé temporairement. Veuillez payer avant l\'heure du RDV pour confirmer.',
                'data' => $appointment->load('service')
            ], 201);
        });
    }

    /**
     * VUE GÉNÉRALE : Liste des rendez-vous filtrée selon le rôle.
     *
     * Médecin  → GET /api/medecin/appointments
     *            ?today=1 filtre uniquement les RDV du jour (utilisé par MedecinHomePage)
     *            Sans paramètre : tous les RDV Confirme du médecin (MedecinAppointmentsPage)
     *
     * Patient  → GET /api/appointments
     *            Tous ses RDV (En_attente + Confirme + Termine) pour afficher l'historique
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'medecin') {
            $query = Appointment::with(['service', 'patient.user', 'invoice'])
                ->where('medecin_id', $user->id)
                ->whereIn('statut', ['En_attente', 'Accepte', 'Confirme'])
                ->orderBy('date_rdv', 'asc');

            // Filtre ?today=1 pour MedecinApiService.getAppointments(todayOnly: true)
            if ($request->boolean('today')) {
                $query->whereDate('date_rdv', Carbon::today());
            }

            $appointments = $query->get();

            // Formater pour correspondre à Appointment.fromApiMap() côté Flutter
            // qui lit patient_user { nom, email, telephone }
            $data = $appointments->map(function ($appt) {
                return [
                    'id'              => $appt->id,
                    'medecin_id'      => $appt->medecin_id,
                    'patient_user_id' => $appt->patient_id,
                    'motif'           => $appt->motif,
                    'urgence'         => $appt->urgence ?? 'Normal',
                    'pieces_jointes'  => $appt->pieces_jointes ?? [],
                    'statut'          => $appt->statut,
                    'date_heure'      => $appt->date_rdv,
                    'meeting_url'     => $appt->meeting_url,
                    'montant'         => $appt->invoice?->montant,
                    'payment_ref'     => $appt->invoice?->transaction_id,
                    'patient_user'    => $appt->patient?->user ? [
                        'nom'       => $appt->patient->user->nom,
                        'email'     => $appt->patient->user->email,
                        'telephone' => $appt->patient->user->telephone,
                    ] : null,
                    'service'         => $appt->service ? [
                        'id'          => $appt->service->id,
                        'nom_service' => $appt->service->nom_service,
                        'prix'        => $appt->service->prix,
                        'mode'        => $appt->service->mode,
                    ] : null,
                ];
            });

        } else {
            // Le patient voit tous ses RDV (pour pouvoir payer ceux en attente)
            $appointments = Appointment::with(['service', 'medecin.user', 'invoice'])
                ->where('patient_id', $user->id)
                ->orderBy('date_rdv', 'desc')
                ->get();

            $data = $appointments;
        }

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }

    /**
     * GET /api/medecin/appointments/today-count
     * Retourne le nombre de RDV confirmés du jour pour le médecin connecté.
     * Utilisé par MedecinHomePage via MedecinApiService.getTodayAppointmentsCount().
     * Flutter attend : { count: int }
     */
    public function todayCount(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'medecin') {
            return response()->json(['message' => 'Accès réservé aux médecins.'], 403);
        }

        $count = Appointment::where('medecin_id', $user->id)
            ->where('statut', 'Confirme')
            ->whereDate('date_rdv', Carbon::today())
            ->count();

        return response()->json([
            'status' => true,
            'count'  => $count,
        ]);
    }

    /**
     * POST /api/medecin/appointments/{id}/accept
     * Le médecin accepte la demande → le patient peut maintenant payer.
     */
    public function acceptByMedecin(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'medecin') {
            return response()->json(['status' => false, 'message' => 'Accès réservé aux médecins.'], 403);
        }

        $appointment = Appointment::with(['patient.user', 'service'])
            ->where('id', $id)
            ->where('medecin_id', $user->id)
            ->first();

        if (!$appointment) {
            return response()->json(['status' => false, 'message' => 'Rendez-vous introuvable.'], 404);
        }

        if ($appointment->statut !== 'En_attente') {
            return response()->json([
                'status'  => false,
                'message' => 'Seule une demande en attente peut être acceptée.',
            ], 422);
        }

        $appointment->update(['statut' => 'Accepte']);

        AppNotification::notify(
            (int) $appointment->patient_id,
            'Demande acceptée ✅',
            'Le Dr. '.$user->nom.' a accepté votre demande. Vous pouvez maintenant procéder au paiement.',
            'appointment_accepted',
            ['appointment_id' => $appointment->id]
        );

        return response()->json([
            'status'  => true,
            'message' => 'Demande acceptée. Le patient peut maintenant payer.',
            'data'    => $appointment->fresh(['service', 'patient.user', 'invoice']),
        ]);
    }

    /**
     * POST /api/medecin/appointments/{id}/refuse
     * Le médecin refuse la demande.
     */
    public function refuseByMedecin(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'medecin') {
            return response()->json(['status' => false, 'message' => 'Accès réservé aux médecins.'], 403);
        }

        $validated = $request->validate([
            'raison' => 'nullable|string|max:255',
        ]);

        $appointment = Appointment::where('id', $id)
            ->where('medecin_id', $user->id)
            ->first();

        if (!$appointment) {
            return response()->json(['status' => false, 'message' => 'Rendez-vous introuvable.'], 404);
        }

        if ($appointment->statut !== 'En_attente') {
            return response()->json([
                'status'  => false,
                'message' => 'Seule une demande en attente peut être refusée.',
            ], 422);
        }

        $appointment->update(['statut' => 'Annule']);
        $this->releaseCreneau($appointment);

        $raison = $validated['raison'] ?? 'Aucune raison précisée.';
        AppNotification::notify(
            (int) $appointment->patient_id,
            'Demande refusée ❌',
            'Le Dr. '.$user->nom.' a refusé votre demande. Raison : '.$raison,
            'appointment_refused',
            ['appointment_id' => $appointment->id]
        );

        return response()->json([
            'status'  => true,
            'message' => 'Demande refusée.',
        ]);
    }

    /**
     * POST /api/medecin/appointments/{id}/cancel
     */
    public function cancelByMedecin(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'medecin') {
            return response()->json(['status' => false, 'message' => 'Accès réservé aux médecins.'], 403);
        }

        $appointment = Appointment::where('id', $id)
            ->where('medecin_id', $user->id)
            ->first();

        if (!$appointment) {
            return response()->json(['status' => false, 'message' => 'Rendez-vous introuvable.'], 404);
        }

        if ($appointment->statut !== 'Confirme') {
            return response()->json([
                'status'  => false,
                'message' => 'Seul un rendez-vous confirmé peut être annulé.',
            ], 422);
        }

        $appointment->update(['statut' => 'Annule']);
        $this->releaseCreneau($appointment);

        AppNotification::notify(
            (int) $appointment->patient_id,
            'Rendez-vous annulé',
            'Le médecin a annulé votre rendez-vous.',
            'appointment_cancelled',
            ['appointment_id' => $appointment->id]
        );

        return response()->json([
            'status'  => true,
            'message' => 'Rendez-vous annulé.',
        ]);
    }

    /**
     * GET /api/patient/doctors/{id}/slots/{date}
     */
    public function patientSlots(Request $request, $id, $date)
    {
        return $this->getAvailableSlots($request, $id, $date);
    }

    /**
     * Récupérer les créneaux disponibles pour un médecin à une date donnée.
     * Uniquement les créneaux publiés (pas de fallback 08h–17h).
     */
    public function getAvailableSlots(Request $request, $medecin_id, $date)
    {
        $request->merge([
            'medecin_id' => $medecin_id,
            'date'       => $date,
        ]);

        $request->validate([
            'medecin_id' => 'required|exists:medecins,user_id',
            'date'       => 'required|date|after_or_equal:today',
        ]);

        $medecinId = $request->medecin_id;
        $date = $request->date;

        $takenTimes = Appointment::where('medecin_id', $medecinId)
            ->whereDate('date_rdv', $date)
            ->whereIn('statut', ['En_attente', 'Accepte', 'Confirme'])
            ->get()
            ->map(fn ($app) => Carbon::parse($app->date_rdv)->format('H:i'))
            ->toArray();

        // Inclure aussi disponible=false pour afficher les slots occupés comme unavailable
        $published = Creneau::where('medecin_id', $medecinId)
            ->whereDate('date', $date)
            ->orderBy('heure_debut')
            ->get();

        $slots = [];
        foreach ($published as $creneau) {
            $time = substr((string) $creneau->heure_debut, 0, 5);
            $slotAt = Carbon::parse($date . ' ' . $time);
            $slots[] = [
                'time'       => $time,
                'available'  => $creneau->disponible
                    && !in_array($time, $takenTimes, true)
                    && !$slotAt->isPast(),
                'creneau_id' => $creneau->id,
            ];
        }

        return response()->json([
            'status' => true,
            'data'   => $slots,
        ]);
    }

    private function releaseCreneau(Appointment $appointment): void
    {
        if (!$appointment->date_rdv) {
            return;
        }
        $date = $appointment->date_rdv->toDateString();
        $time = $appointment->date_rdv->format('H:i');

        Creneau::where('medecin_id', $appointment->medecin_id)
            ->whereDate('date', $date)
            ->get()
            ->each(function (Creneau $creneau) use ($time) {
                if (substr((string) $creneau->heure_debut, 0, 5) === $time) {
                    $creneau->update(['disponible' => true]);
                }
            });
    }
}