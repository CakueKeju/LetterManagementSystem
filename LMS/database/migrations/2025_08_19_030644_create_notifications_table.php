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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('surat_id')->constrained('surat')->onDelete('cascade');
            $table->string('type', 50); // 'new_letter', 'letter_access_granted', etc.
            $table->string('title', 255);
            $table->text('message');
            $table->json('data')->nullable(); // Additional data like sender info, etc.
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['user_id', 'is_read'], 'idx_notifications_user_read');
            $table->index(['user_id', 'created_at'], 'idx_notifications_user_date');
            $table->index('surat_id', 'idx_notifications_surat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
