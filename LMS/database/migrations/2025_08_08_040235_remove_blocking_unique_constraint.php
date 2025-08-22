<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // ================================= MIGRATION UP =================================
    
    public function up(): void
    {
        // Try to drop the index, ignore if it doesn't exist
        try {
            Schema::table('surat', function (Blueprint $table) {
                $table->dropUnique('idx_surat_nomor_divisi_jenis_unique');
            });
        } catch (Exception $e) {
            // Index doesn't exist, ignore the error
        }
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
