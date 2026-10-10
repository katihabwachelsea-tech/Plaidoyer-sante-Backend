<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ajoute 'Accepte' à l'ENUM statut de la table appointments.
     *
     * L'ENUM original était : En_attente, Confirme, Annule, Termine
     * Le code utilise également : Accepte (médecin accepte → patient paie)
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("
            ALTER TABLE appointments
            MODIFY COLUMN statut
            ENUM('En_attente','Accepte','Confirme','Annule','Termine')
            NOT NULL DEFAULT 'En_attente'
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Repasse sur l'ENUM sans 'Accepte'
        // (les lignes avec statut='Accepte' seront tronquées → En_attente)
        DB::statement("
            ALTER TABLE appointments
            MODIFY COLUMN statut
            ENUM('En_attente','Confirme','Annule','Termine')
            NOT NULL DEFAULT 'En_attente'
        ");
    }
};
