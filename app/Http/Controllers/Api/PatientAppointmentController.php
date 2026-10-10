<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
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
     * Accepte urgence + pièces jointes (multipart/form-data).
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'patient') {
            return response()->json(['status' => false, 'message' => 'Accès réservé aux patients.'], 403);
        }

        $validated = $request->validate([
            'medecin_id'      => 'required|exists:medecins,user_id',
            'service_id'      => 'required|exists:services,id',
            'date_rdv'        => 'required|date|after:now',
            'motif'           => 'required|string',
            'urgence'         => 'nullable|in:Normal,Urgent,Tres_urgent',
            'pieces_jointes'  => 'nullable|array|max:5',
            'pieces_jointes.*'=> 'file|mimes:jpeg,png,jpg,pdf|max:5120',
        ]);

        // Upload des pièces jointes
        $uploadedFiles = [];
        if ($request->hasFile('pieces_jointes')) {
            foreach ($request->file('pieces_jointes') as $file) {
                $path = $file->store('public/appointments');
                $uploadedFiles[] = \Illuminate\Support\Facades\Storage::url($path);
            }
        }

        $slot = Carbon::parse($validated['date_rdv']);
        $date = $slot->toDateString();
        $time = $slot->format('H:i');

        // Le médecin doit avoir publié ce créneau (pas de slots inventés)
        $published = Creneau::where('medecin_id', $validated['medecin_id'])
            ->whereDate('date', $date)
            ->where('disponible', true)
            ->get();

        $matchedCreneau = $published->first(function ($creneau) use ($time) {
            return substr((string) $creneau->heure_debut, 0, 5) === $time;
        });

        if (!$matchedCreneau) {
            return response()->json([
                'status'  => false,
                'message' => 'Ce créneau ne fait pas partie des disponibilités du médecin.',
            ], 422);
        }

        // Occupé si En_attente, Accepte ou Confirme (évite le double-booking)
        $dejaPris = Appointment::where('medecin_id', $validated['medecin_id'])
            ->where('date_rdv', $validated['date_rdv'])
            ->whereIn('statut', ['En_attente', 'Accepte', 'Confirme'])
            ->exists();

        if ($dejaPris) {
            return response()->json([
                'status'  => false,
                'message' => 'Ce créneau est déjà réservé ou en attente de validation.',
            ], 422);
        }

        $service = Service::findOrFail($validated['service_id']);

        $appointment = DB::transaction(function () use ($validated, $user, $service, $uploadedFiles, $matchedCreneau) {
            $appointment = Appointment::create([
                'patient_id'     => $user->id,
                'medecin_id'     => $validated['medecin_id'],
                'service_id'     => $validated['service_id'],
                'date_rdv'       => $validated['date_rdv'],
                'motif'          => $validated['motif'],
                'urgence'        => $validated['urgence'] ?? 'Normal',
                'pieces_jointes' => !empty($uploadedFiles) ? $uploadedFiles : null,
                'statut'         => 'En_attente',
                'meeting_url'    => null,
            ]);

            $matchedCreneau->update(['disponible' => false]);

            if ($service->mode === 'teleconsultation') {
                $appointment->load('medecin.user');
                $appointment->update([
                    'meeting_url' => $this->buildMeetingUrl(
                        $appointment->id,
                        $appointment->medecin?->user?->telephone
                    ),
                ]);
            }

            Invoice::create([
                'appointment_id'  => $appointment->id,
                'montant'         => $service->prix,
                'statut_paiement' => 'Non_paye',
                'date_emission'   => now(),
            ]);

            return $appointment->load(['service', 'medecin.user', 'invoice']);
        });

        $label = Carbon::parse($appointment->date_rdv)->locale('fr')->isoFormat('ddd D MMM HH:mm');
        AppNotification::notify(
            $user->id,
            'Rendez-vous réservé',
            "Votre RDV du $label est en attente de validation par le médecin.",
            'appointment_created',
            ['appointment_id' => $appointment->id]
        );
        AppNotification::notify(
            (int) $validated['medecin_id'],
            'Nouvelle demande de RDV' . (($validated['urgence'] ?? 'Normal') !== 'Normal' ? ' ⚠️ '.$validated['urgence'] : ''),
            ($user->nom ?? 'Un patient')." a réservé le $label.",
            'appointment_created',
            ['appointment_id' => $appointment->id]
        );

        return response()->json([
            'status'  => true,
            'message' => 'Demande envoyée. En attente de validation du médecin.',
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

        // Avant paiement uniquement : En_attente ou Accepte (pas Confirme)
        if (!in_array($appointment->statut, ['En_attente', 'Accepte'], true)) {
            return response()->json([
                'status'  => false,
                'message' => 'Ce rendez-vous ne peut plus être annulé (déjà confirmé ou terminé).',
            ], 422);
        }

        if ($appointment->date_rdv && $appointment->date_rdv->isPast()) {
            return response()->json([
                'status'  => false,
                'message' => 'Impossible d\'annuler un rendez-vous déjà passé.',
            ], 422);
        }

        $appointment->update(['statut' => 'Annule']);
        $this->releaseCreneau($appointment);

        AppNotification::notify(
            (int) $appointment->medecin_id,
            'Rendez-vous annulé',
            ($user->nom ?? 'Le patient').' a annulé un rendez-vous.',
            'appointment_cancelled',
            ['appointment_id' => $appointment->id]
        );

        return response()->json([
            'status'  => true,
            'message' => 'Rendez-vous annulé.',
            'data'    => $this->format($appointment->fresh(['service', 'medecin.user', 'invoice'])),
        ]);
    }

    /**
     * POST /api/patient/appointments/{id}/pay
     */
    public function pay(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'patient') {
            return response()->json(['status' => false, 'message' => 'Accès réservé aux patients.'], 403);
        }

        $appointment = Appointment::with(['invoice', 'service', 'medecin.user'])
            ->where('id', $id)
            ->where('patient_id', $user->id)
            ->first();

        if (!$appointment || !$appointment->invoice) {
            return response()->json(['status' => false, 'message' => 'Facture introuvable pour ce rendez-vous.'], 404);
        }

        if ($appointment->invoice->statut_paiement === 'Paye') {
            return response()->json(['status' => false, 'message' => 'Cette facture est déjà réglée.'], 422);
        }

        if ($appointment->statut === 'Annule') {
            return response()->json([
                'status'  => false,
                'message' => 'Ce rendez-vous a été annulé.',
            ], 422);
        }

        // Bloquer le paiement seulement si le RDV est passé depuis plus de 2h
        // (délai de grâce pour permettre le paiement le jour J)
        if ($appointment->date_rdv <= now()->subHours(2)) {
            return response()->json([
                'status'  => false,
                'message' => 'Ce créneau a expiré. Vous ne pouvez plus le payer.',
            ], 422);
        }

        // Le patient ne peut payer que si le médecin a accepté
        if ($appointment->statut !== 'Accepte') {
            return response()->json([
                'status'  => false,
                'message' => 'Ce rendez-vous ne peut pas être payé : le médecin doit d\'abord accepter la demande.',
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

        $label = Carbon::parse($appointment->date_rdv)->locale('fr')->isoFormat('ddd D MMM HH:mm');
        AppNotification::notify(
            $user->id,
            'Rendez-vous confirmé',
            "Paiement reçu. RDV confirmé le $label.",
            'appointment_confirmed',
            ['appointment_id' => $appointment->id]
        );
        AppNotification::notify(
            (int) $appointment->medecin_id,
            'RDV confirmé',
            ($user->nom ?? 'Un patient')." a payé — RDV le $label.",
            'appointment_confirmed',
            ['appointment_id' => $appointment->id]
        );

        return response()->json([
            'status'         => true,
            'message'        => 'Paiement réussi. Votre rendez-vous est confirmé.',
            'transaction_id' => $appointment->invoice->transaction_id,
            'rdv_statut'     => 'Confirme',
            'data'           => $this->format($appointment),
        ]);
    }

    /** Libère le créneau lié à un RDV annulé / refusé. */
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

    private function buildMeetingUrl(int $appointmentId, ?string $doctorPhone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $doctorPhone);
        if ($digits && strlen($digits) >= 8) {
            if (!str_starts_with($digits, '257') && strlen($digits) <= 9) {
                $digits = '257'.$digits;
            }
            $text = rawurlencode("Bonjour Docteur, je rejoins la téléconsultation (RDV #$appointmentId).");

            return "https://wa.me/{$digits}?text={$text}";
        }

        // Fallback Meet (salle nominative pour ce RDV)
        return 'https://meet.google.com/lookup/plaidoyer-'.$appointmentId;
    }

    private function format(Appointment $appt): array
    {
        return [
            'id'          => $appt->id,
            'medecin_id'  => $appt->medecin_id,
            'service_id'  => $appt->service_id,
            'date_rdv'    => optional($appt->date_rdv)->format('Y-m-d H:i:s'),
            'motif'       => $appt->motif,
            'urgence'     => $appt->urgence ?? 'Normal',
            'pieces_jointes' => $appt->pieces_jointes ?? [],
            'statut'      => $appt->statut,
            'meeting_url' => $appt->meeting_url,
            'medecin'     => $appt->medecin ? [
                'user_id'    => $appt->medecin->user_id,
                'specialite' => $appt->medecin->specialite,
                'hopital'    => $appt->medecin->hopital,
                'user'       => $appt->medecin->user ? [
                    'nom'       => $appt->medecin->user->nom,
                    'email'     => $appt->medecin->user->email,
                    'telephone' => $appt->medecin->user->telephone,
                    'photo_url' => $appt->medecin->user->photo_url,
                ] : null,
            ] : null,
            'service' => $appt->service ? [
                'id'          => $appt->service->id,
                'nom_service' => $appt->service->nom_service,
                'prix'        => $appt->service->prix,
                'mode'        => $appt->service->mode,
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
