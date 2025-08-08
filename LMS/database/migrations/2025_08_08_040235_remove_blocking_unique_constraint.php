<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // ================================= MIGRATION UP =================================
    
    public function up(): void
    {
        Schema::table('surat', function (Blueprint $table) {
            // hapus constraint yang ngeblok nomor per bulan
            $table->dropUnique('idx_surat_nomor_divisi_jenis_unique');
        });
    }

    // ================================= MIGRATION DOWN =================================
    
    public function down(): void
    {
        Schema::table('surat', function (Blueprint $table) {
            // balikin constraint kalo perlu
            $table->unique(['nomor_urut', 'divisi_id', 'jenis_surat_id'], 'idx_surat_nomor_divisi_jenis_unique');
        });
    }
};
