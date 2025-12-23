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
            ])
            ->default('pending')
            ->charset('utf8mb4')
            ->collation('utf8mb4_unicode_ci')
            ->change();
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
            ])
            ->default('pending')
            ->charset('utf8mb4')
            ->collation('utf8mb4_unicode_ci')
            ->change();
        });
    }
};
