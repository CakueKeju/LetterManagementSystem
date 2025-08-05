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
        Schema::create('counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jenis_surat_id')->constrained('jenis_surat')->onDelete('cascade');
            $table->string('month_year', 7); // Format: YYYY-MM
            $table->integer('counter')->default(0);
            $table->timestamps();
            
            // Ensure one counter per jenis_surat per month
            $table->unique(['jenis_surat_id', 'month_year']);
            
            // Index for faster queries
            $table->index(['jenis_surat_id', 'month_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('counters');
    }
};
