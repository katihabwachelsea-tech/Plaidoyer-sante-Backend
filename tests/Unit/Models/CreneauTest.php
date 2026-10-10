<?php

namespace Tests\Unit\Models;

use App\Models\Creneau;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CreneauTest extends TestCase
{
    public function test_slot_uses_its_table_and_casts_date_and_availability(): void
    {
        $creneau = new Creneau([
            'date' => '2026-10-10',
            'disponible' => 1,
        ]);

        $this->assertSame('creneaux', $creneau->getTable());
        $this->assertInstanceOf(Carbon::class, $creneau->date);
        $this->assertTrue($creneau->disponible);
    }

    public function test_slot_belongs_to_the_doctor_user(): void
    {
        $relation = (new Creneau)->medecin();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertSame(User::class, $relation->getRelated()::class);
        $this->assertSame('medecin_id', $relation->getForeignKeyName());
    }
}
