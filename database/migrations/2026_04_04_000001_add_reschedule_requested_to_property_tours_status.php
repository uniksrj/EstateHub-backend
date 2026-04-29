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
        Schema::table('property_tours', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'approved',
                'completed',
                'cancelled',
                'rejected',
                'reschedule_requested',
            ])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('property_tours', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'approved',
                'completed',
                'cancelled',
                'rejected',
            ])->default('pending')->change();
        });
    }
};

