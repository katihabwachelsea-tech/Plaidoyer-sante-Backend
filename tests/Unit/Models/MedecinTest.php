<?php

namespace Tests\Unit\Models;

use App\Models\Appointment;
use App\Models\Medecin;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class MedecinTest extends TestCase
{
    public function test_doctor_uses_user_id_as_a_non_incrementing_primary_key(): void
    {
        $medecin = new Medecin;

        $this->assertSame('user_id', $medecin->getKeyName());
        $this->assertFalse($medecin->getIncrementing());
    }

    public function test_validation_status_is_cast_to_boolean(): void
    {
        $medecin = new Medecin(['is_validated' => 1]);

        $this->assertTrue($medecin->is_validated);
    }

    public function test_doctor_relations_target_user_appointments_and_services(): void
    {
        $medecin = new Medecin;

        $this->assertInstanceOf(BelongsTo::class, $medecin->user());
        $this->assertSame(User::class, $medecin->user()->getRelated()::class);
        $this->assertInstanceOf(HasMany::class, $medecin->appointments());
        $this->assertSame(Appointment::class, $medecin->appointments()->getRelated()::class);
        $this->assertInstanceOf(BelongsToMany::class, $medecin->services());
        $this->assertSame(Service::class, $medecin->services()->getRelated()::class);
    }
}
