<?php

namespace Tests\Unit\Models;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Invoice;
use App\Models\Medecin;
use App\Models\Patient;
use App\Models\Service;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    public function test_appointment_casts_its_date_and_attachments(): void
    {
        $appointment = new Appointment([
            'date_rdv' => '2026-10-10 10:30:00',
            'pieces_jointes' => ['document.pdf'],
        ]);

        $this->assertInstanceOf(Carbon::class, $appointment->date_rdv);
        $this->assertSame(['document.pdf'], $appointment->pieces_jointes);
    }

    public function test_appointment_relations_target_its_patient_doctor_and_service(): void
    {
        $appointment = new Appointment;

        $this->assertInstanceOf(BelongsTo::class, $appointment->patient());
        $this->assertSame(Patient::class, $appointment->patient()->getRelated()::class);
        $this->assertInstanceOf(BelongsTo::class, $appointment->medecin());
        $this->assertSame(Medecin::class, $appointment->medecin()->getRelated()::class);
        $this->assertInstanceOf(BelongsTo::class, $appointment->service());
        $this->assertSame(Service::class, $appointment->service()->getRelated()::class);
    }

    public function test_appointment_has_an_invoice_and_a_consultation(): void
    {
        $appointment = new Appointment;

        $this->assertInstanceOf(HasOne::class, $appointment->invoice());
        $this->assertSame(Invoice::class, $appointment->invoice()->getRelated()::class);
        $this->assertInstanceOf(HasOne::class, $appointment->consultation());
        $this->assertSame(Consultation::class, $appointment->consultation()->getRelated()::class);
    }
}
