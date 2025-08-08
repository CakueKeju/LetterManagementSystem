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
        Schema::table('surat', function (Blueprint $table) {
            // Drop the current constraint that doesn't include date
            $table->dropUnique('idx_surat_nomor_divisi_jenis_unique');
            
            // Add new constraint that includes the date to allow same nomor_urut in different months
            // This allows the same nomor_urut for the same divisi and jenis_surat but in different dates
            $table->unique(['nomor_urut', 'divisi_id', 'jenis_surat_id', 'tanggal_surat'], 'idx_surat_nomor_divisi_jenis_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surat', function (Blueprint $table) {
            // Drop the date-inclusive constraint
            $table->dropUnique('idx_surat_nomor_divisi_jenis_date_unique');
            
            // Restore the old constraint without date
            $table->unique(['nomor_urut', 'divisi_id', 'jenis_surat_id'], 'idx_surat_nomor_divisi_jenis_unique');
        });
    }
};
