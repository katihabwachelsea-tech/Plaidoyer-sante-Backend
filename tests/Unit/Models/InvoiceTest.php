<?php

namespace Tests\Unit\Models;

use App\Models\Appointment;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    public function test_invoice_belongs_to_an_appointment(): void
    {
        $relation = (new Invoice)->appointment();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertSame(Appointment::class, $relation->getRelated()::class);
        $this->assertSame('appointment_id', $relation->getForeignKeyName());
    }

    public function test_payment_fields_are_mass_assignable(): void
    {
        $fillable = (new Invoice)->getFillable();

        $this->assertContains('montant', $fillable);
        $this->assertContains('statut_paiement', $fillable);
        $this->assertContains('transaction_id', $fillable);
    }
}
