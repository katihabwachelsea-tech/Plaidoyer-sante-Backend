<?php

namespace Tests\Unit\Models;

use App\Models\Appointment;
use App\Models\Medecin;
use App\Models\Service;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    public function test_service_has_appointments_and_doctors(): void
    {
        $service = new Service;

        $this->assertInstanceOf(HasMany::class, $service->appointments());
        $this->assertSame(Appointment::class, $service->appointments()->getRelated()::class);
        $this->assertInstanceOf(BelongsToMany::class, $service->medecins());
        $this->assertSame(Medecin::class, $service->medecins()->getRelated()::class);
    }

    public function test_service_fields_are_mass_assignable(): void
    {
        $fillable = (new Service)->getFillable();

        $this->assertContains('nom_service', $fillable);
        $this->assertContains('prix', $fillable);
        $this->assertContains('mode', $fillable);
    }
}
