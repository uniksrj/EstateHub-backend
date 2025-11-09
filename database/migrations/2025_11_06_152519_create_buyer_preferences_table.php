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
        Schema::create('buyer_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->decimal('min_price', 10, 2)->nullable();
            $table->decimal('max_price', 10, 2)->nullable();
            $table->integer('min_bedrooms')->nullable();
            $table->integer('min_bathrooms')->nullable();
            $table->string('property_type')->nullable();
            $table->string('location')->nullable();
            $table->integer('min_sqft')->nullable();
            $table->integer('max_sqft')->nullable();
            $table->json('amenities')->nullable();
            $table->boolean('alerts_enabled')->default(true);
            $table->string('alert_frequency')->default('instant');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buyer_preferences');
    }
};
