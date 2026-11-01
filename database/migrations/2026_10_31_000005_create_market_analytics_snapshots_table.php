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
        Schema::create('market_analytics_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('city', 100);
            $table->string('locality', 150)->nullable();
            $table->date('snapshot_date');
            $table->decimal('median_price_per_sqft', 12, 2);
            $table->decimal('median_rent_per_sqft', 8, 2);
            $table->decimal('yoy_appreciation_pct', 6, 3);
            $table->decimal('avg_gross_yield', 6, 3);
            $table->unsignedInteger('active_listings');
            $table->unsignedInteger('avg_days_on_market');
            $table->timestamps();

            $table->index(['city', 'locality', 'snapshot_date']);
            $table->index('snapshot_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('market_analytics_snapshots');
    }
};
