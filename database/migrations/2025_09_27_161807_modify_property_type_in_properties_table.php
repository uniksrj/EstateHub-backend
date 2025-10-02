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
            $table->string('property_type', 255)
                ->charset('utf8mb4')
                ->collation('utf8mb4_unicode_ci')
                ->nullable(false)
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            // Roll back to previous definition. Adjust as needed:
            $table->string('property_type', 255)->nullable()->change();
        });
    }
};
