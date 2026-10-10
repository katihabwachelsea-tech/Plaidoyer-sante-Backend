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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients', 'user_id');
            $table->foreignId('medecin_id')->constrained('medecins', 'user_id');
            $table->foreignId('service_id')->constrained('services');
            $table->dateTime('date_rdv');
            $table->text('motif');
            $table->enum('statut', ['En_attente', 'Accepte', 'Confirme', 'Annule', 'Termine'])->default('En_attente');
            $table->timestamps();
        });

        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
