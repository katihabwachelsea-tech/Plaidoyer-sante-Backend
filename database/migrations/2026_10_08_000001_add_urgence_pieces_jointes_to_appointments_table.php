<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'urgence')) {
                $table->enum('urgence', ['Normal', 'Urgent', 'Tres_urgent'])
                    ->default('Normal')
                    ->after('motif');
            }
            if (!Schema::hasColumn('appointments', 'pieces_jointes')) {
                $table->json('pieces_jointes')->nullable()->after('urgence');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'pieces_jointes')) {
                $table->dropColumn('pieces_jointes');
            }
            if (Schema::hasColumn('appointments', 'urgence')) {
                $table->dropColumn('urgence');
            }
        });
    }
};
