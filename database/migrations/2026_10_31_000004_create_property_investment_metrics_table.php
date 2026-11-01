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
        Schema::create('property_investment_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('gross_yield', 6, 3);
            $table->decimal('net_yield', 6, 3);
            $table->decimal('cap_rate', 6, 3);
            $table->decimal('noi_annual', 14, 2);
            $table->decimal('dscr', 6, 3)->nullable();
            $table->decimal('cash_on_cash', 6, 3)->nullable();
            $table->decimal('occupancy_rate', 5, 2)->nullable();
            $table->unsignedTinyInteger('investment_score'); // 0-100
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_investment_metrics');
    }
};
