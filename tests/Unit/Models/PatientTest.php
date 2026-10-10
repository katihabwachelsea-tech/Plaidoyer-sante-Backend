<?php

namespace Tests\Unit\Models;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\TestCase;

class PatientTest extends TestCase
{
    public function test_patient_uses_user_id_as_a_non_incrementing_primary_key(): void
    {
        $patient = new Patient;

        $this->assertSame('user_id', $patient->getKeyName());
        $this->assertFalse($patient->getIncrementing());
    }

    public function test_patient_belongs_to_its_user(): void
    {
        $relation = (new Patient)->user();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertSame(User::class, $relation->getRelated()::class);
        $this->assertSame('user_id', $relation->getForeignKeyName());
    }

    public function test_medical_profile_fields_are_mass_assignable(): void
    {
        $this->assertContains('allergies', (new Patient)->getFillable());
        $this->assertContains('antecedents', (new Patient)->getFillable());
    }
}
