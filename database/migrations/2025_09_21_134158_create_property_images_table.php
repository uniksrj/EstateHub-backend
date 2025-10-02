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
        Schema::create('property_images', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY

            // Relationship to properties
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');

            $table->string('image_path', 255);
            $table->boolean('is_primary')->default(false);
            $table->string('caption', 255)->nullable();
            $table->unsignedSmallInteger('order_index')->default(0);

            $table->timestamps(); // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_images');
    }
};
