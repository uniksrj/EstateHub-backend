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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();

            $table->string('title', 255);
            $table->text('description');
            $table->decimal('price', 12, 2);

            $table->enum('property_type', ['house', 'apartment', 'condo', 'villa', 'townhouse']);
            $table->enum('status', ['for_sale', 'sold', 'pending', 'draft'])->default('draft');
            $table->boolean('featured')->default(false);

            // Address Information
            $table->string('address', 255);
            $table->string('city', 100);
            $table->string('state', 100);
            $table->string('zip_code', 20);
            $table->string('country', 100)->default('India');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // Property Details
            $table->unsignedSmallInteger('bedrooms');
            $table->unsignedSmallInteger('bathrooms');
            $table->unsignedInteger('sq_ft');
            $table->decimal('lot_size', 8, 2)->nullable();
            $table->year('year_built')->nullable();
            $table->unsignedSmallInteger('garage')->default(0);

            // Amenities (Boolean flags)
            $table->boolean('has_pool')->default(false);
            $table->boolean('has_garden')->default(false);
            $table->boolean('has_garage')->default(false);
            $table->boolean('has_parking')->default(false);
            $table->boolean('has_security')->default(false);
            $table->boolean('has_air_conditioning')->default(false);
            $table->boolean('has_heating')->default(false);

            // Relationships
            $table->foreignId('agent_id')->constrained('users')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
