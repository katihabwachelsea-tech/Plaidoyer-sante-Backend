<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La migration originale crée disponibilite en boolean (true/false).
     * Flutter envoie et affiche une string comme "Lundi-Vendredi • 08:00-17:00".
     * Cette migration change le type en string nullable.
     *
     * Note : La migration 2026_04_01_074220_change_disponibilite_type_in_medecins_table.php
     * a peut-être déjà effectué ce changement. Vérifier avant d'appliquer.
     */
    public function up(): void
    {
        // On vérifie d'abord si la colonne est encore un boolean
        // Si la migration précédente a déjà changé le type, cette migration est idempotente
        Schema::table('medecins', function (Blueprint $table) {
            $table->string('disponibilite')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('medecins', function (Blueprint $table) {
            $table->boolean('disponibilite')->default(false)->change();
        });
    }
};
