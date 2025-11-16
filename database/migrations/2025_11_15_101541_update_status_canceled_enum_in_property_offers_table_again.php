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
        Schema::table('property_offers', function (Blueprint $table) {
            $table->enum('status', [
                'pending', 
                'accepted', 
                'rejected', 
                'counter_offer', 
                'expired', 
                'cancelled', 
                'withdrawn'
            ])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('property_offers', function (Blueprint $table) {
            $table->enum('status', [
                'pending', 
                'accepted', 
                'rejected', 
                'counter_offer',
                'expired',
            ])->default('pending')->change();
        });
    }
};
