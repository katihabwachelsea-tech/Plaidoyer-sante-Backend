<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')
                  ->constrained('appointments')
                  ->onDelete('cascade');
            $table->foreignId('sender_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->text('contenu');
            $table->timestamp('lu_at')->nullable(); // null = non lu
            $table->timestamps();

            $table->index(['appointment_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
