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
        Schema::create('medecins', function (Blueprint $table) {
           // $table->id();
            $table->foreignId('user_id')->primary()->constrained('users')->onDelete('cascade');
            $table->string('specialite')->nullable();
            $table->string('licence')->unique()->nullable();
            $table->string('hopital')->nullable();
            $table->text('biographie')->nullable();
            //  Disponibilité du médecin (Pour apparaître dans la liste)
            $table->boolean('disponibilite')->default(false);
            //  Approbation de l'admin (Indispensable pour la sécurité)
            $table->boolean('is_validated')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medecins');
    }
};
