<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->onDelete('cascade');
            $table->double('montant');
            $table->enum('statut_paiement', ['Non_paye', 'Paye'])->default('Non_paye');
            $table->enum('methode_paiement', ['Lumicash', 'Ecocash'])->nullable();
            $table->string('transaction_id')->nullable();
            $table->timestamp('date_emission')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
