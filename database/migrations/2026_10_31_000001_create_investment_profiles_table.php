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
        Schema::create('investment_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('risk_appetite', ['low', 'moderate', 'aggressive'])->default('moderate');
            $table->decimal('budget_min', 14, 2)->nullable();
            $table->decimal('budget_max', 14, 2)->nullable();
            $table->decimal('target_cap_rate', 5, 2)->nullable();
            $table->decimal('target_cash_on_cash', 5, 2)->nullable();
            $table->unsignedSmallInteger('hold_period_years')->default(5);
            $table->json('preferred_cities')->nullable();
            $table->json('preferred_property_types')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_profiles');
    }
};
