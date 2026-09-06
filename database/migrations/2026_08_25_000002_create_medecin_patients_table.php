<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée la table du carnet de patients géré par le médecin.
     * Correspond aux données synchronisées depuis SQLite Flutter
     * via ApiService.fetchPatients() / createPatient() / updatePatient() / deletePatient().
     */
    public function up(): void
    {
        Schema::create('medecin_patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medecin_id')->constrained('users')->onDelete('cascade');
            $table->string('nom', 100);
            $table->string('prenom', 100);
            $table->unsignedSmallInteger('age')->nullable();
            $table->string('pays', 100)->nullable();
            $table->string('maladie', 255)->nullable();
            $table->text('conseils')->nullable();
            $table->date('date_creation')->nullable();
            $table->date('derniere_visite')->nullable();
            $table->timestamps();

            $table->index('medecin_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medecin_patients');
    }
};
