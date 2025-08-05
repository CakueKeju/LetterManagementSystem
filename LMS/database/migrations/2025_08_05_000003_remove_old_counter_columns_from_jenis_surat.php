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
        Schema::table('jenis_surat', function (Blueprint $table) {
            // Drop the old counter columns since we now use jenis_surat_counters table
            $table->dropColumn(['counter', 'last_reset_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jenis_surat', function (Blueprint $table) {
            // Restore the old counter columns if needed to rollback
            $table->integer('counter')->default(0)->after('is_active');
            $table->string('last_reset_month', 7)->nullable()->after('counter'); // Format: YYYY-MM
        });
    }
};
