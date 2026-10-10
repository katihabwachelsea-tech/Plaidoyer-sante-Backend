<?php

namespace Tests\Feature;

use App\Models\Medecin;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientMedecinFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_and_doctor_booking_flow(): void
    {
        $patientUser = User::create([
            'nom' => 'Aline Ndayishimiye',
            'email' => 'aline@example.com',
            'password' => 'secret123',
            'telephone' => '79000001',
            'role' => 'patient',
            'is_profile_completed' => true,
        ]);
        Patient::create([
            'user_id' => $patientUser->id,
            'date_naissance' => '1995-04-12',
            'groupe_sanguin' => 'O+',
        ]);

        $doctorUser = User::create([
            'nom' => 'Dr Karim',
            'email' => 'karim@example.com',
            'password' => 'secret123',
            'telephone' => '79000002',
            'role' => 'medecin',
            'is_profile_completed' => true,
        ]);
        Medecin::create([
            'user_id' => $doctorUser->id,
            'specialite' => 'Cardiologie',
            'licence' => 'LIC-001',
            'hopital' => 'CHUK',
            'is_validated' => true,
            'disponibilite' => 'Lun-Ven 08:00-17:00',
        ]);

        $service = Service::create([
            'nom_service' => 'Consultation',
            'prix' => 15000,
        ]);

        $slotDate = now()->addDay()->format('Y-m-d');
        $this->actingAs($doctorUser, 'sanctum')
            ->postJson('/api/medecin/creneaux', [
                'date' => $slotDate,
                'heure_debut' => '10:00',
                'heure_fin' => '10:45',
                'disponible' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('status', true);

        $this->actingAs($patientUser, 'sanctum')
            ->getJson("/api/patient/doctors/{$doctorUser->id}/slots/{$slotDate}")
            ->assertOk()
            ->assertJsonPath('data.0.time', '10:00')
            ->assertJsonPath('data.0.available', true);

        $booked = $this->actingAs($patientUser, 'sanctum')
            ->postJson('/api/patient/appointments', [
                'medecin_id' => $doctorUser->id,
                'service_id' => $service->id,
                'date_rdv' => $slotDate . ' 10:00:00',
                'motif' => 'Contrôle cardiaque',
            ])
            ->assertCreated()
            ->assertJsonPath('data.statut', 'En_attente');

        $appointmentId = $booked->json('data.id');

        // Le médecin doit accepter avant le paiement
        $this->actingAs($doctorUser, 'sanctum')
            ->postJson("/api/medecin/appointments/{$appointmentId}/accept")
            ->assertOk()
            ->assertJsonPath('data.statut', 'Accepte');

        $this->actingAs($patientUser, 'sanctum')
            ->postJson("/api/patient/appointments/{$appointmentId}/pay", [
                'methode_paiement' => 'Ecocash',
                'telephone' => '79000001',
            ])
            ->assertOk()
            ->assertJsonPath('rdv_statut', 'Confirme');

        $this->actingAs($doctorUser, 'sanctum')
            ->getJson('/api/medecin/dashboard')
            ->assertOk()
            ->assertJsonPath('data.upcoming_count', 1);

        $this->actingAs($doctorUser, 'sanctum')
            ->getJson('/api/medecin/appointments')
            ->assertOk()
            ->assertJsonPath('data.0.statut', 'Confirme');

        // Consultation autorisée uniquement le jour J — forcer la date du RDV à aujourd'hui
        \App\Models\Appointment::where('id', $appointmentId)->update([
            'date_rdv' => now()->setTime(10, 0),
        ]);

        $this->actingAs($doctorUser, 'sanctum')
            ->postJson('/api/consultations', [
                'rendez_vous_id' => $appointmentId,
                'diagnostic' => 'Tension stable',
                'ordonnance' => 'Repos 48h',
                'notes' => 'Revoir dans un mois',
                'signature' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            ])
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->actingAs($patientUser, 'sanctum')
            ->getJson('/api/patient/medical-record')
            ->assertOk()
            ->assertJsonPath('data.0.diagnostic', 'Tension stable');

        $this->actingAs($patientUser, 'sanctum')
            ->getJson('/api/patient/profile')
            ->assertOk()
            ->assertJsonPath('data.nom', 'Aline Ndayishimiye');
    }
}
