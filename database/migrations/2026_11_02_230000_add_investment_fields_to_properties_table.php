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
        Schema::table('properties', function (Blueprint $table) {
            if (!Schema::hasColumn('properties', 'is_investment_friendly')) {
                $table->boolean('is_investment_friendly')->default(true)->after('featured');
                $table->index('is_investment_friendly');
            }
            if (!Schema::hasColumn('properties', 'locality')) {
                $table->string('locality', 150)->nullable()->after('city');
                $table->index('locality');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            if (Schema::hasColumn('properties', 'is_investment_friendly')) {
                $table->dropColumn('is_investment_friendly');
            }
            if (Schema::hasColumn('properties', 'locality')) {
                $table->dropColumn('locality');
            }
        });
    }
};
