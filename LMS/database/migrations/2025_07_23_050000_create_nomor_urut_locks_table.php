<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nomor_urut_locks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('divisi_id');
            $table->unsignedBigInteger('jenis_surat_id')->nullable();
            $table->integer('nomor_urut');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('locked_until')->nullable();
            $table->timestamps();
            $table->unique(['divisi_id', 'jenis_surat_id', 'nomor_urut'], 'lock_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomor_urut_locks');
    }
}; 