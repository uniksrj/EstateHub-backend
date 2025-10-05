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
        Schema::table('user_preferences', function (Blueprint $table) {
            // Seller-specific
            $table->string('selling_timeline')->nullable();
            $table->string('property_address')->nullable();

            // Agent/Broker-specific
            $table->string('license_number')->nullable();
            $table->string('agency_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->dropColumn([
                'selling_timeline',
                'property_address',
                'license_number',
                'agency_name'
            ]);
        });
    }
};
