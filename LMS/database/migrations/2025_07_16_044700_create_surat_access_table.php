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
        Schema::create('surat_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_id')->constrained('surat');
            $table->foreignId('user_id')->constrained('users');
            $table->timestamp('granted_at')->useCurrent();
            $table->string('notes', 255)->nullable();
            $table->unique(['surat_id', 'user_id'], 'idx_surat_access_unique');
            $table->index('surat_id', 'idx_surat_access_surat');
            $table->index('user_id', 'idx_surat_access_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_access');
    }
};
