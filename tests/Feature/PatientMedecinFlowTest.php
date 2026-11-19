<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Medecin;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PatientMedecinFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_and_doctor_booking_flow(): void
    {
        Storage::fake('public');

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
            'biographie' => 'Suivi cardiovasculaire et prévention.',
            'is_validated' => true,
            'disponibilite' => 'Lun-Ven 08:00-17:00',
        ]);

        $service = Service::create([
            'nom_service' => 'Consultation',
            'prix' => 15000,
        ]);

        $this->actingAs($patientUser, 'sanctum')
            ->getJson('/api/patient/doctors?search=CHUK')
            ->assertOk()
            ->assertJsonPath('data.0.user.nom', 'Dr Karim')
            ->assertJsonPath('data.0.biographie', 'Suivi cardiovasculaire et prévention.');

        $this->actingAs($patientUser, 'sanctum')
            ->getJson('/api/patient/doctors?search=Karim')
            ->assertOk()
            ->assertJsonPath('data.0.user.nom', 'Dr Karim');

        $this->actingAs($patientUser, 'sanctum')
            ->getJson('/api/medecins/autocomplete?q=HUK')
            ->assertOk()
            ->assertJsonPath('data.0.user.nom', 'Dr Karim')
            ->assertJsonPath('data.0.biographie', 'Suivi cardiovasculaire et prévention.');

        $this->actingAs($patientUser, 'sanctum')
            ->getJson('/api/patient/doctors/autocomplete?q=Cardio')
            ->assertOk()
            ->assertJsonPath('data.0.user.nom', 'Dr Karim');

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
                'date_rdv' => $slotDate.' 10:00:00',
                'motif' => 'Contrôle cardiaque',
            ])
            ->assertCreated()
            ->assertJsonPath('data.statut', 'En_attente');

        $appointmentId = $booked->json('data.id');

        $this->actingAs($patientUser, 'sanctum')
            ->getJson('/api/conversations')
            ->assertOk()
            ->assertJsonPath('current_user.id', $patientUser->id)
            ->assertJsonPath('current_user.role', 'patient')
            ->assertJsonPath('data.0.appointment_id', $appointmentId)
            ->assertJsonPath('data.0.last_message', null);

        $this->actingAs($patientUser, 'sanctum')
            ->postJson("/api/appointments/{$appointmentId}/messages", [
                'contenu' => 'Bonjour docteur',
            ])
            ->assertCreated()
            ->assertJsonPath('current_user.id', $patientUser->id)
            ->assertJsonPath('data.sender_nom', $patientUser->nom)
            ->assertJsonPath('data.sender_role', 'patient')
            ->assertJsonPath('data.is_mine', true);

        $this->actingAs($doctorUser, 'sanctum')
            ->getJson('/api/conversations')
            ->assertOk()
            ->assertJsonPath('current_user.id', $doctorUser->id)
            ->assertJsonPath('current_user.role', 'medecin')
            ->assertJsonPath('data.0.other_name', $patientUser->nom)
            ->assertJsonPath('data.0.other_role', 'patient')
            ->assertJsonPath('data.0.last_message', 'Bonjour docteur')
            ->assertJsonPath(
                'data.0.last_message_sender_id',
                $patientUser->id
            );

        $this->actingAs($doctorUser, 'sanctum')
            ->getJson("/api/appointments/{$appointmentId}/messages")
            ->assertOk()
            ->assertJsonPath('current_user.id', $doctorUser->id)
            ->assertJsonPath('data.0.sender_nom', $patientUser->nom)
            ->assertJsonPath('data.0.sender_role', 'patient')
            ->assertJsonPath('data.0.is_mine', false);

        $this->actingAs($patientUser, 'sanctum')
            ->getJson('/api/conversations')
            ->assertOk()
            ->assertJsonPath('current_user.id', $patientUser->id)
            ->assertJsonPath('data.0.other_name', $doctorUser->nom)
            ->assertJsonPath('data.0.other_role', 'medecin');

        $this->actingAs($patientUser, 'sanctum')
            ->getJson("/api/appointments/{$appointmentId}/messages")
            ->assertOk()
            ->assertJsonPath('current_user.id', $patientUser->id)
            ->assertJsonPath('data.0.sender_nom', $patientUser->nom)
            ->assertJsonPath('data.0.sender_role', 'patient')
            ->assertJsonPath('data.0.is_mine', true);

        $this->actingAs($doctorUser, 'sanctum')
            ->postJson("/api/appointments/{$appointmentId}/messages", [
                'contenu' => 'Bonjour Estimé',
            ])
            ->assertCreated()
            ->assertJsonPath('data.sender_nom', $doctorUser->nom)
            ->assertJsonPath('data.sender_role', 'medecin')
            ->assertJsonPath('data.is_mine', true);

        $this->actingAs($patientUser, 'sanctum')
            ->getJson("/api/appointments/{$appointmentId}/messages")
            ->assertOk()
            ->assertJsonPath('data.1.sender_nom', $doctorUser->nom)
            ->assertJsonPath('data.1.sender_role', 'medecin')
            ->assertJsonPath('data.1.is_mine', false);

        // Le paiement doit rester bloqué tant que le médecin n'a pas accepté.
        $this->actingAs($patientUser, 'sanctum')
            ->postJson("/api/patient/appointments/{$appointmentId}/pay", [
                'methode_paiement' => 'Ecocash',
                'telephone' => '79000001',
            ])
            ->assertUnprocessable();

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

        // Consultation autorisée le jour J et dès les 5 minutes précédant l'heure prévue.
        Appointment::where('id', $appointmentId)->update([
            'date_rdv' => now()->subMinute(),
        ]);

        $consultation = $this->actingAs($doctorUser, 'sanctum')
            ->postJson('/api/consultations', [
                'rendez_vous_id' => $appointmentId,
                'diagnostic' => 'Tension stable',
                'ordonnance' => 'Repos 48h',
                'notes' => 'Revoir dans un mois',
                'signature' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            ])
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.ordonnance', 'Repos 48h');

        $signaturePath = $consultation->json('data.signature_path');
        $this->assertNotEmpty($signaturePath);
        Storage::disk('public')->assertExists(
            str_replace('/storage/', '', $signaturePath)
        );

        $this->actingAs($patientUser, 'sanctum')
            ->getJson('/api/patient/medical-record')
            ->assertOk()
            ->assertJsonPath('data.0.diagnostic', 'Tension stable')
            ->assertJsonPath('data.0.ordonnance', 'Repos 48h')
            ->assertJsonPath('data.0.signature_path', $signaturePath);

        $this->actingAs($doctorUser, 'sanctum')
            ->getJson("/api/medecin/patients/{$patientUser->id}/dossier")
            ->assertOk()
            ->assertJsonPath('data.profil.nom', $patientUser->nom)
            ->assertJsonPath('data.historique.0.diagnostic', 'Tension stable')
            ->assertJsonPath(
                'data.historique.0.appointment.invoice.statut_paiement',
                'Paye'
            );

        $otherDoctor = User::create([
            'nom' => 'Dr Hors suivi',
            'email' => 'other-doctor@example.com',
            'password' => 'secret123',
            'role' => 'medecin',
            'is_profile_completed' => true,
        ]);

        $this->actingAs($otherDoctor, 'sanctum')
            ->getJson("/api/medecin/patients/{$patientUser->id}/dossier")
            ->assertForbidden();

        $this->actingAs($patientUser, 'sanctum')
            ->getJson("/api/consultations/{$appointmentId}")
            ->assertOk()
            ->assertJsonPath('data.ordonnance', 'Repos 48h')
            ->assertJsonPath('data.signature_path', $signaturePath);

        $this->actingAs($patientUser, 'sanctum')
            ->getJson('/api/patient/appointments')
            ->assertOk()
            ->assertJsonPath('data.0.statut', 'Termine');

        $this->actingAs($patientUser, 'sanctum')
            ->getJson('/api/patient/profile')
            ->assertOk()
            ->assertJsonPath('data.nom', 'Aline Ndayishimiye');
    }
}
