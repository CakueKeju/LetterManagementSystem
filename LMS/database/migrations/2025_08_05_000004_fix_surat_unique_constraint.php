<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat', function (Blueprint $table) {
            // Drop the incorrect unique constraint
            $table->dropUnique('idx_surat_nomor_divisi_unique');
            
            // Add the correct unique constraint that includes jenis_surat_id
            $table->unique(['nomor_urut', 'divisi_id', 'jenis_surat_id'], 'idx_surat_nomor_divisi_jenis_unique');
        });
    }

    public function down(): void
    {
        Schema::table('surat', function (Blueprint $table) {
            // Drop the correct constraint
            $table->dropUnique('idx_surat_nomor_divisi_jenis_unique');
            
            // Restore the old incorrect constraint
            $table->unique(['nomor_urut', 'divisi_id'], 'idx_surat_nomor_divisi_unique');
        });
    }
};
