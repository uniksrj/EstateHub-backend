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
            $table->id(); // BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY

            // Relationship to properties
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');

            $table->string('name', 255);
            $table->string('email', 255);
            $table->string('phone', 20)->nullable();
            $table->text('message');

            $table->enum('status', ['new', 'contacted', 'viewed', 'closed'])->default('new');
            $table->enum('source', ['website', 'phone', 'email', 'walk-in'])->default('website');

            $table->timestamps(); // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
