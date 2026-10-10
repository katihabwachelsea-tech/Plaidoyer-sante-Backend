<?php

namespace Tests\Unit\Models;

use App\Models\MedecinPatient;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MedecinPatientTest extends TestCase
{
    public function test_doctor_patient_entry_uses_its_table_and_casts_profile_fields(): void
    {
        $entry = new MedecinPatient([
            'age' => '42',
            'date_creation' => '2026-10-10',
            'derniere_visite' => '2026-10-09',
        ]);

        $this->assertSame('medecin_patients', $entry->getTable());
        $this->assertSame(42, $entry->age);
        $this->assertInstanceOf(Carbon::class, $entry->date_creation);
        $this->assertInstanceOf(Carbon::class, $entry->derniere_visite);
    }

    public function test_doctor_patient_entry_belongs_to_a_doctor_user(): void
    {
        $relation = (new MedecinPatient)->medecin();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertSame(User::class, $relation->getRelated()::class);
        $this->assertSame('medecin_id', $relation->getForeignKeyName());
    }
}
