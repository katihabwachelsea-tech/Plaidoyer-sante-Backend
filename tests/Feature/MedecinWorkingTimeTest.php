<?php

namespace Tests\Feature;

use App\Models\Medecin;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedecinWorkingTimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_recurring_schedule_generates_patient_slots_and_accepts_booking(): void
    {
        $doctor = User::create([
            'nom' => 'Dr Amina',
            'email' => 'amina@example.com',
            'password' => 'secret123',
            'telephone' => '79000011',
            'role' => 'medecin',
            'is_profile_completed' => true,
        ]);
        Medecin::create([
            'user_id' => $doctor->id,
            'specialite' => 'Médecine générale',
            'licence' => 'LIC-WT-001',
            'hopital' => 'CHUK',
            'is_validated' => true,
            'disponibilite' => 'Hebdomadaire',
        ]);

        $patient = User::create([
            'nom' => 'Patient Test',
            'email' => 'patient-wt@example.com',
            'password' => 'secret123',
            'telephone' => '79000012',
            'role' => 'patient',
            'is_profile_completed' => true,
        ]);
        Patient::create(['user_id' => $patient->id]);

        $service = Service::create([
            'nom_service' => 'Consultation',
            'prix' => 15000,
        ]);

        $slotDay = now()->addDay();
        $slotDate = $slotDay->toDateString();
        $bookingDate = $slotDay->copy()->addWeek()->toDateString();
        $exceptionDate = $slotDay->copy()->addWeeks(2)->toDateString();
        $weekday = $slotDay->dayOfWeekIso;
        $days = [];
        for ($day = 1; $day <= 7; $day++) {
            $days[] = [
                'weekday' => $day,
                'active' => $day === $weekday,
                'duration_override_minutes' => null,
                'slots' => [['start' => '20:00', 'end' => '21:00']],
            ];
        }

        $this->actingAs($doctor, 'sanctum')
            ->putJson('/api/medecin/working-time', [
                'global_duration_minutes' => 30,
                'days' => $days,
                'exceptions' => [['date' => $exceptionDate, 'label' => 'Congé']],
            ])
            ->assertOk()
            ->assertJsonPath('data.days.0.weekday', 1);

        $this->actingAs($doctor, 'sanctum')
            ->getJson('/api/medecin/working-time')
            ->assertOk()
            ->assertJsonPath('data.global_duration_minutes', 30)
            ->assertJsonPath('data.exceptions.0.date', $exceptionDate);

        $scheduleSlots = $this->actingAs($doctor, 'sanctum')
            ->getJson("/api/medecin/creneaux?from={$slotDate}&to={$slotDate}");
        $scheduleSlots
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.date', $slotDate)
            ->assertJsonPath('data.0.generated_by_working_time', true);

        $patientSlots = $this->actingAs($patient, 'sanctum')
            ->getJson("/api/patient/doctors/{$doctor->id}/slots/{$slotDate}");
        $this->assertSame(200, $patientSlots->status(), $patientSlots->getContent());
        $patientSlots
            ->assertJsonPath('data.0.time', '20:00')
            ->assertJsonPath('data.0.available', true)
            ->assertJsonPath('data.1.time', '20:30')
            ->assertJsonPath('data.1.available', true);

        $this->actingAs($patient, 'sanctum')
            ->postJson('/api/patient/appointments', [
                'medecin_id' => $doctor->id,
                'service_id' => $service->id,
                'date_rdv' => "$bookingDate 20:00:00",
                'motif' => 'Consultation',
            ])
            ->assertCreated()
            ->assertJsonPath('data.statut', 'En_attente');

        $this->actingAs($patient, 'sanctum')
            ->getJson("/api/patient/doctors/{$doctor->id}/slots/{$bookingDate}")
            ->assertOk()
            ->assertJsonPath('data.0.available', false)
            ->assertJsonPath('data.1.available', true);

        $this->actingAs($patient, 'sanctum')
            ->getJson("/api/patient/doctors/{$doctor->id}/slots/{$exceptionDate}")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_overlapping_weekly_windows_are_rejected(): void
    {
        $doctor = User::create([
            'nom' => 'Dr Validation',
            'email' => 'validation-wt@example.com',
            'password' => 'secret123',
            'telephone' => '79000013',
            'role' => 'medecin',
            'is_profile_completed' => true,
        ]);

        $days = [];
        for ($day = 1; $day <= 7; $day++) {
            $days[] = [
                'weekday' => $day,
                'active' => false,
                'duration_override_minutes' => null,
                'slots' => [
                    ['start' => '08:00', 'end' => '10:00'],
                    ['start' => '09:00', 'end' => '11:00'],
                ],
            ];
        }

        $this->actingAs($doctor, 'sanctum')
            ->putJson('/api/medecin/working-time', [
                'global_duration_minutes' => 30,
                'days' => $days,
                'exceptions' => [],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['days.0.slots.1.start']);
    }
}
