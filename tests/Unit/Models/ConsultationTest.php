<?php

namespace Tests\Unit\Models;

use App\Models\Appointment;
use App\Models\Consultation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\TestCase;

class ConsultationTest extends TestCase
{
    public function test_structured_medical_data_is_cast_to_arrays(): void
    {
        $consultation = new Consultation([
            'constantes_vitales' => ['temperature' => 37],
            'medicaments' => [['nom' => 'Paracétamol']],
            'examens_complementaires' => ['Analyse sanguine'],
        ]);

        $this->assertSame(['temperature' => 37], $consultation->constantes_vitales);
        $this->assertSame([['nom' => 'Paracétamol']], $consultation->medicaments);
        $this->assertSame(['Analyse sanguine'], $consultation->examens_complementaires);
    }

    public function test_consultation_belongs_to_an_appointment(): void
    {
        $relation = (new Consultation)->appointment();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertSame(Appointment::class, $relation->getRelated()::class);
        $this->assertSame('appointment_id', $relation->getForeignKeyName());
    }
}
