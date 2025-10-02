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
       Schema::create('user_preferences', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('preferred_location')->nullable();
        $table->decimal('min_budget', 12, 2)->nullable();
        $table->decimal('max_budget', 12, 2)->nullable();
        $table->string('property_type')->nullable();
        $table->integer('bedrooms')->nullable();
        $table->integer('bathrooms')->nullable();
        $table->string('move_in_timeline')->nullable();
        $table->boolean('newsletter')->default(false);
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
