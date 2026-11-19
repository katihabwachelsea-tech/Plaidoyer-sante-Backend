<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medecin_working_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medecin_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('global_duration_minutes');
            $table->json('days');
            $table->json('exceptions');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medecin_working_times');
    }
};
