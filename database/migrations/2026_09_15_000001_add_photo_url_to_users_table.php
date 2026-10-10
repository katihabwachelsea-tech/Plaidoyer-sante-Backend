<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute le champ photo_url à la table users.
     * Stocke l'URL de la photo de profil du médecin (upload ou lien externe).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('photo_url')->nullable()->after('telephone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('photo_url');
        });
    }
};
