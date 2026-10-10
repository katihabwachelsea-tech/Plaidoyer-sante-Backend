<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            // Notes SOAP structurées
            if (!Schema::hasColumn('consultations', 'soap_s')) {
                $table->text('soap_s')->nullable()->comment('Subjectif — plaintes patient')->after('anamnese');
            }
            if (!Schema::hasColumn('consultations', 'soap_o')) {
                $table->text('soap_o')->nullable()->comment('Objectif — constantes et examen physique')->after('soap_s');
            }
            if (!Schema::hasColumn('consultations', 'soap_a')) {
                $table->text('soap_a')->nullable()->comment('Assessment — analyse/évaluation clinique')->after('soap_o');
            }
            if (!Schema::hasColumn('consultations', 'soap_p')) {
                $table->text('soap_p')->nullable()->comment('Plan — traitement et suivi')->after('soap_a');
            }

            // Constantes vitales en JSON (TA, pouls, T°, poids, taille, SpO2…)
            if (!Schema::hasColumn('consultations', 'constantes_vitales')) {
                $table->json('constantes_vitales')->nullable()->after('soap_p');
            }

            // Médicaments structurés en JSON (liste d'objets médicament)
            if (!Schema::hasColumn('consultations', 'medicaments')) {
                $table->json('medicaments')->nullable()->after('constantes_vitales');
            }

            // Examens complémentaires par spécialité
            if (!Schema::hasColumn('consultations', 'examens_complementaires')) {
                $table->json('examens_complementaires')->nullable()->after('medicaments');
            }
        });
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            foreach ([
                'soap_s', 'soap_o', 'soap_a', 'soap_p',
                'constantes_vitales', 'medicaments', 'examens_complementaires',
            ] as $col) {
                if (Schema::hasColumn('consultations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
