<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\AppNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * php artisan appointments:expire
 *
 * Auto-refuse les rendez-vous restés "En_attente" (aucune réponse du médecin)
 * dont la date de RDV est passée.
 *
 * Planifié toutes les 30 minutes dans Kernel.php pour réagir rapidement
 * le jour J sans attendre minuit.
 */
class ExpireAppointments extends Command
{
    protected $signature   = 'appointments:expire';
    protected $description = 'Auto-refuse les RDV En_attente dont la date est dépassée sans réponse du médecin.';

    public function handle(): int
    {
        // Tous les RDV encore "En_attente" dont l'heure est passée
        $expired = Appointment::with(['patient.user', 'medecin.user'])
            ->where('statut', 'En_attente')
            ->where('date_rdv', '<=', Carbon::now())
            ->get();

        if ($expired->isEmpty()) {
            $this->info('Aucun RDV expiré à traiter.');
            return Command::SUCCESS;
        }

        $count = 0;

        foreach ($expired as $appointment) {
            // Passer le statut à Annule
            $appointment->update(['statut' => 'Annule']);

            // Récupérer les noms pour les notifications
            $patientNom  = $appointment->patient?->user?->nom  ?? 'Patient';
            $medecinNom  = $appointment->medecin?->user?->nom  ?? 'le médecin';
            $dateLabel   = Carbon::parse($appointment->date_rdv)
                ->locale('fr')
                ->isoFormat('dddd D MMMM YYYY [à] HH:mm');

            // ── Notification au patient ───────────────────────────────────
            AppNotification::notify(
                (int) $appointment->patient_id,
                'Rendez-vous expiré',
                "Votre demande du $dateLabel avec Dr. $medecinNom n'a pas reçu de réponse. "
                . "Elle a été annulée automatiquement. Veuillez soumettre une nouvelle demande.",
                'appointment_expired',
                ['appointment_id' => $appointment->id]
            );

            // ── Notification au médecin (information) ────────────────────
            AppNotification::notify(
                (int) $appointment->medecin_id,
                'RDV expiré sans réponse',
                "La demande de $patientNom du $dateLabel a été annulée automatiquement "
                . "car elle n'avait pas été traitée avant l'heure du rendez-vous.",
                'appointment_expired',
                ['appointment_id' => $appointment->id]
            );

            $count++;
            $this->line("RDV #{$appointment->id} expiré — patient: $patientNom");
        }

        $this->info("$count rendez-vous expiré(s) traité(s).");

        return Command::SUCCESS;
    }
}
