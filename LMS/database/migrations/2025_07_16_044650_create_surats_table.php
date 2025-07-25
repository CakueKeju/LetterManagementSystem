<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat', function (Blueprint $table) {
            $table->id();
            $table->integer('nomor_urut');
            $table->string('nomor_surat', 50)->unique();
            $table->foreignId('divisi_id')->constrained('divisions');
            $table->foreignId('jenis_surat_id')->constrained('jenis_surat');
            $table->string('perihal', 255);
            $table->date('tanggal_surat');
            $table->date('tanggal_diterima');
            $table->string('file_path', 500);
            $table->integer('file_size')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->boolean('is_private')->default(false);
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
            $table->unique(['nomor_urut', 'divisi_id'], 'idx_surat_nomor_divisi_unique');
            $table->index('divisi_id', 'idx_surat_divisi');
            $table->index('jenis_surat_id', 'idx_surat_jenis');
            $table->index('uploaded_by', 'idx_surat_uploader');
            $table->index('tanggal_surat', 'idx_surat_tanggal');
            $table->index('is_private', 'idx_surat_privacy');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat');
    }
};
