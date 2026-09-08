<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Creneau;
use App\Models\Invoice;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatientAppointmentController extends Controller
{
    /**
     * GET /api/patient/appointments
     * Liste dédiée des rendez-vous du patient connecté.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'patient') {
            return response()->json(['status' => false, 'message' => 'Accès réservé aux patients.'], 403);
        }

        $appointments = Appointment::with(['service', 'medecin.user', 'invoice'])
            ->where('patient_id', $user->id)
            ->orderBy('date_rdv', 'desc')
            ->get()
            ->map(fn (Appointment $appt) => $this->format($appt));

        return response()->json([
            'status' => true,
            'data'   => $appointments,
        ]);
    }

    /**
     * POST /api/patient/appointments
     * Réservation d'un créneau + facture en attente.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'patient') {
            return response()->json(['status' => false, 'message' => 'Accès réservé aux patients.'], 403);
        }

        $validated = $request->validate([
            'medecin_id' => 'required|exists:medecins,user_id',
            'service_id' => 'required|exists:services,id',
            'date_rdv'   => 'required|date|after:now',
            'motif'      => 'required|string',
        ]);

        $slot = Carbon::parse($validated['date_rdv']);
        $date = $slot->toDateString();
        $time = $slot->format('H:i');

        $published = Creneau::where('medecin_id', $validated['medecin_id'])
            ->whereDate('date', $date)
            ->where('disponible', true)
            ->get();

        if ($published->isNotEmpty()) {
            $matches = $published->contains(function ($creneau) use ($time) {
                return substr((string) $creneau->heure_debut, 0, 5) === $time;
            });
            if (!$matches) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Ce créneau ne fait pas partie des disponibilités du médecin.',
                ], 422);
            }
        }

        $dejaPris = Appointment::where('medecin_id', $validated['medecin_id'])
            ->where('date_rdv', $validated['date_rdv'])
            ->where(function ($query) {
                $query->where('statut', 'Confirme')
                    ->orWhere(function ($q) {
                        $q->where('statut', 'En_attente')->where('date_rdv', '>', now());
                    });
            })
            ->exists();

        if ($dejaPris) {
            return response()->json([
                'status'  => false,
                'message' => 'Ce créneau est déjà réservé ou en attente de paiement.',
            ], 422);
        }

        $service = Service::findOrFail($validated['service_id']);

        $appointment = DB::transaction(function () use ($validated, $user, $service) {
            $appointment = Appointment::create([
                'patient_id' => $user->id,
                'medecin_id' => $validated['medecin_id'],
                'service_id' => $validated['service_id'],
                'date_rdv'   => $validated['date_rdv'],
                'motif'      => $validated['motif'],
                'statut'     => 'En_attente',
            ]);

            Invoice::create([
                'appointment_id'  => $appointment->id,
                'montant'         => $service->prix,
                'statut_paiement' => 'Non_paye',
                'date_emission'   => now(),
            ]);

            return $appointment->load(['service', 'medecin.user', 'invoice']);
        });

        return response()->json([
            'status'  => true,
            'message' => 'Créneau réservé. Veuillez payer pour confirmer le rendez-vous.',
            'data'    => $this->format($appointment),
        ], 201);
    }

    /**
     * POST /api/patient/appointments/{id}/cancel
     */
    public function cancel(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'patient') {
            return response()->json(['status' => false, 'message' => 'Accès réservé aux patients.'], 403);
        }

        $appointment = Appointment::where('id', $id)
            ->where('patient_id', $user->id)
            ->first();

        if (!$appointment) {
            return response()->json(['status' => false, 'message' => 'Rendez-vous introuvable.'], 404);
        }

        if (!in_array($appointment->statut, ['En_attente', 'Confirme'], true)) {
            return response()->json([
                'status'  => false,
                'message' => 'Ce rendez-vous ne peut plus être annulé.',
            ], 422);
        }

        if ($appointment->date_rdv && $appointment->date_rdv->isPast()) {
            return response()->json([
                'status'  => false,
                'message' => 'Impossible d\'annuler un rendez-vous déjà passé.',
            ], 422);
        }

        $appointment->update(['statut' => 'Annule']);

        return response()->json([
            'status'  => true,
            'message' => 'Rendez-vous annulé.',
            'data'    => $this->format($appointment->fresh(['service', 'medecin.user', 'invoice'])),
        ]);
    }

    /**
     * POST /api/patient/appointments/{id}/pay
     * Paiement simulé (Ecocash / Lumicash) qui confirme le RDV.
     */
    public function pay(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'patient') {
            return response()->json(['status' => false, 'message' => 'Accès réservé aux patients.'], 403);
        }

        $appointment = Appointment::with('invoice')
            ->where('id', $id)
            ->where('patient_id', $user->id)
            ->first();

        if (!$appointment || !$appointment->invoice) {
            return response()->json(['status' => false, 'message' => 'Facture introuvable pour ce rendez-vous.'], 404);
        }

        if ($appointment->invoice->statut_paiement === 'Paye') {
            return response()->json(['status' => false, 'message' => 'Cette facture est déjà réglée.'], 422);
        }

        if ($appointment->statut === 'Annule' || $appointment->date_rdv <= now()) {
            return response()->json([
                'status'  => false,
                'message' => 'Ce créneau a expiré ou a été annulé. Vous ne pouvez plus le payer.',
            ], 422);
        }

        $validated = $request->validate([
            'methode_paiement' => 'required|in:Ecocash,Lumicash',
            'telephone'        => 'required|string|min:8',
        ]);

        $transactionId = strtoupper($validated['methode_paiement']) . '-' . now()->format('YmdHis') . '-' . random_int(100, 999);

        DB::transaction(function () use ($appointment, $validated, $transactionId) {
            $appointment->invoice->update([
                'statut_paiement'  => 'Paye',
                'methode_paiement' => $validated['methode_paiement'],
                'transaction_id'   => $transactionId,
            ]);
            $appointment->update(['statut' => 'Confirme']);
        });

        $appointment->refresh()->load(['service', 'medecin.user', 'invoice']);

        return response()->json([
            'status'         => true,
            'message'        => 'Paiement réussi. Votre rendez-vous est confirmé.',
            'transaction_id' => $appointment->invoice->transaction_id,
            'rdv_statut'     => 'Confirme',
            'data'           => $this->format($appointment),
        ]);
    }

    private function format(Appointment $appt): array
    {
        return [
            'id'         => $appt->id,
            'medecin_id' => $appt->medecin_id,
            'service_id' => $appt->service_id,
            'date_rdv'   => optional($appt->date_rdv)->format('Y-m-d H:i:s'),
            'motif'      => $appt->motif,
            'statut'     => $appt->statut,
            'medecin'    => $appt->medecin ? [
                'user_id'    => $appt->medecin->user_id,
                'specialite' => $appt->medecin->specialite,
                'hopital'    => $appt->medecin->hopital,
                'user'       => $appt->medecin->user ? [
                    'nom'   => $appt->medecin->user->nom,
                    'email' => $appt->medecin->user->email,
                ] : null,
            ] : null,
            'service' => $appt->service ? [
                'id'          => $appt->service->id,
                'nom_service' => $appt->service->nom_service,
                'prix'        => $appt->service->prix,
            ] : null,
            'invoice' => $appt->invoice ? [
                'id'               => $appt->invoice->id,
                'montant'          => $appt->invoice->montant,
                'statut_paiement'  => $appt->invoice->statut_paiement,
                'methode_paiement' => $appt->invoice->methode_paiement,
                'transaction_id'   => $appt->invoice->transaction_id,
            ] : null,
        ];
    }
}
