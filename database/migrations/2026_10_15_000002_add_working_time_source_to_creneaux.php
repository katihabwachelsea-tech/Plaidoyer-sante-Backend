<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('creneaux', function (Blueprint $table) {
            $table->boolean('generated_by_working_time')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('creneaux', function (Blueprint $table) {
            $table->dropColumn('generated_by_working_time');
        });
    }
};
