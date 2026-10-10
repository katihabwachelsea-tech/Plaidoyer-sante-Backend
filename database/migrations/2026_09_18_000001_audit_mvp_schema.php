<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'mode')) {
                $table->string('mode', 32)->default('presentiel')->after('prix');
            }
            if (!Schema::hasColumn('services', 'specialite')) {
                $table->string('specialite')->nullable()->after('mode');
            }
        });

        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'meeting_url')) {
                $table->string('meeting_url', 500)->nullable()->after('statut');
            }
        });

        Schema::table('consultations', function (Blueprint $table) {
            if (!Schema::hasColumn('consultations', 'anamnese')) {
                $table->text('anamnese')->nullable()->after('appointment_id');
            }
            if (!Schema::hasColumn('consultations', 'examen')) {
                $table->text('examen')->nullable()->after('anamnese');
            }
            if (!Schema::hasColumn('consultations', 'signature_path')) {
                $table->string('signature_path', 500)->nullable()->after('conseils_ia');
            }
        });

        if (!Schema::hasTable('app_notifications')) {
            Schema::create('app_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('title');
                $table->text('body');
                $table->string('type', 64)->default('info');
                $table->json('data')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('app_notifications');

        Schema::table('consultations', function (Blueprint $table) {
            foreach (['anamnese', 'examen', 'signature_path'] as $col) {
                if (Schema::hasColumn('consultations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'meeting_url')) {
                $table->dropColumn('meeting_url');
            }
        });

        Schema::table('services', function (Blueprint $table) {
            foreach (['mode', 'specialite'] as $col) {
                if (Schema::hasColumn('services', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
