<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée la table des créneaux de disponibilité des médecins.
     * Utilisée par MedecinApiService.getCreneaux() / createCreneau().
     */
    public function up(): void
    {
        Schema::create('creneaux', function (Blueprint $table) {
            $table->id();
            // medecin_id référence users.id (le compte User du médecin)
            $table->foreignId('medecin_id')->constrained('users')->onDelete('cascade');
            $table->date('date');
            $table->string('heure_debut', 5); // Format HH:mm ex: "09:00"
            $table->string('heure_fin', 5);   // Format HH:mm ex: "10:00"
            $table->boolean('disponible')->default(true);
            $table->timestamps();

            // Unicité : un médecin ne peut pas avoir deux créneaux au même moment
            $table->unique(['medecin_id', 'date', 'heure_debut'], 'unique_creneau');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creneaux');
    }
};
