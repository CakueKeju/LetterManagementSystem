<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate existing counter data to new table structure
        $jenisSurat = DB::table('jenis_surat')
            ->where('counter', '>', 0)
            ->whereNotNull('last_reset_month')
            ->get();

        foreach ($jenisSurat as $js) {
            // Create counter record for the last reset month
            DB::table('counters')->insert([
                'jenis_surat_id' => $js->id,
                'month_year' => $js->last_reset_month,
                'counter' => $js->counter,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration doesn't need to be reversed as it only migrates data
        // The counters table drop is handled by the main table migration
    }
};
