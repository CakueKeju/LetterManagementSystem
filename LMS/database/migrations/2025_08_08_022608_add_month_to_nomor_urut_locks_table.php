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
        Schema::table('nomor_urut_locks', function (Blueprint $table) {
            $table->string('month_year', 7)->after('nomor_urut')->index(); // Format: YYYY-MM
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nomor_urut_locks', function (Blueprint $table) {
            $table->dropColumn('month_year');
        });
    }
};
