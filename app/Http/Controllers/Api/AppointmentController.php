<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
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
            $query = Appointment::with(['service', 'patient.user'])
                ->where('medecin_id', $user->id)
                ->where('statut', 'Confirme')
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
                    'statut'          => $appt->statut,
                    'date_heure'      => $appt->date_rdv,
                    'montant'         => $appt->invoice?->montant,
                    'payment_ref'     => $appt->invoice?->transaction_id,
                    'patient_user'    => $appt->patient?->user ? [
                        'nom'       => $appt->patient->user->nom,
                        'email'     => $appt->patient->user->email,
                        'telephone' => $appt->patient->user->telephone,
                    ] : null,
                    'service'         => $appt->service,
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
 * Récupérer les créneaux disponibles pour un médecin à une date donnée
 */
public function getAvailableSlots(Request $request, $medecin_id, $date)

{
// 1. On injecte les paramètres de l'URL dans la requête pour que la validation continue de fonctionner
    $request->merge([
        'medecin_id' => $medecin_id,
        'date'       => $date,
    ]);

    $request->validate([
        'medecin_id' => 'required|exists:medecins,user_id',
        'date'=> 'required|date|after_or_equal:today',
    ]);

    $medecinId = $request->medecin_id;
    $date = $request->date; // Format Y-m-d

    // 1. Définir la plage horaire (On peut imaginer 08:00 à 17:00 par défaut)
    $startWork = Carbon::parse($date . ' 08:00:00');
    $endWork   = Carbon::parse($date . ' 17:00:00');
    $interval  = 45; // Minutes

    // 2. Récupérer les RDV déjà réservés pour ce jour-là
    // On considère un créneau pris s'il est "Confirme" ou "En_attente"
    $appointments = Appointment::where('medecin_id', $medecinId)
        ->whereDate('date_rdv', $date)
        ->whereIn('statut', ['Confirme', 'En_attente'])
        ->get()
        ->map(function ($app) {
            return Carbon::parse($app->date_rdv)->format('H:i');
        })
        ->toArray();

    $slots = [];
    $current = $startWork;

    // 3. Générer les créneaux
    while ($current->copy()->addMinutes($interval) <= $endWork) {
        $time = $current->format('H:i');
        
        // Vérifier si le créneau est passé (si on regarde pour "aujourd'hui")
        $isPast = $current->isPast();

        $slots[] = [
            'time'      => $time,
            'available' => !in_array($time, $appointments) && !$isPast,
        ];

        $current->addMinutes($interval);
    }

    return response()->json([
        'status' => true,
        'data'   => $slots
    ]);
}
}