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
        Schema::create('user_activities', function (Blueprint $table) {
            $table->id();
            
            // User who performed the activity
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            
            // Activity type
            $table->string('activity_type'); 
            // Examples: 'login', 'property_view', 'property_inquiry', 
            // 'property_favorite', 'search', 'profile_update'
            
            // Related property (if applicable)
            $table->foreignId('property_id')
                  ->nullable()
                  ->constrained('properties')
                  ->onDelete('cascade');
            
            // Related inquiry (if applicable)
            $table->foreignId('inquiry_id')
                  ->nullable()
                  ->constrained('inquiries')
                  ->onDelete('cascade');
            
            // Additional data stored as JSON
            $table->json('metadata')->nullable();
            // Examples: search terms, filters, page visited, device info
            
            // IP and user agent for analytics
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['user_id', 'activity_type']);
            $table->index(['user_id', 'created_at']);
            $table->index('activity_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_activities');
    }
};
