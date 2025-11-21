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
         Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->text('message');
            $table->enum('status', ['new', 'contacted', 'responded', 'scheduled', 'closed', 'spam'])->default('new');
            $table->enum('source', ['website', 'phone', 'email', 'referral', 'social_media'])->default('website');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->decimal('budget_min', 15, 2)->nullable();
            $table->decimal('budget_max', 15, 2)->nullable();
            $table->string('timeline')->nullable(); // immediate, 1-3 months, 3-6 months, 6+ months
            $table->string('property_type_interest')->nullable();
            $table->string('preferred_location')->nullable();
            $table->integer('bedrooms')->nullable();
            $table->integer('bathrooms')->nullable();
            $table->text('additional_requirements')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('response_notes')->nullable();
            $table->timestamps();

            // Indexes for better performance
            $table->index(['property_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['priority', 'status']);
            $table->index(['source', 'created_at']);
            $table->index('email');
        });
        //  Schema::create('inquiries', function (Blueprint $table) {
        //     $table->id(); // BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY

        //     // Relationship to properties
        //     $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');

        //     $table->string('name', 255);
        //     $table->string('email', 255);
        //     $table->string('phone', 20)->nullable();
        //     $table->text('message');

        //     $table->enum('status', ['new', 'contacted', 'viewed', 'closed'])->default('new');
        //     $table->enum('source', ['website', 'phone', 'email', 'walk-in'])->default('website');

        //     $table->timestamps(); // created_at and updated_at
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiries');
        // Schema::dropIfExists('inquiries');
    }
};
