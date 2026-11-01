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
        Schema::create('portfolio_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained();
            $table->decimal('purchase_price', 14, 2);
            $table->decimal('acquisition_costs', 14, 2)->default(0);
            $table->decimal('monthly_rent', 12, 2)->default(0);
            $table->decimal('monthly_opex', 12, 2)->default(0);
            $table->date('acquired_at');
            $table->enum('status', ['active', 'sold', 'vacant'])->default('active');
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portfolio_properties');
    }
};
