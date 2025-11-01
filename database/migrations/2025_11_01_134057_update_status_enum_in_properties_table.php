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
            $table->enum('status', ['for_sale', 'sold', 'pending', 'draft', 'under_review'])
                  ->default('draft')
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
       Schema::table('properties', function (Blueprint $table) {
            // Rollback to the previous ENUM definition (adjust if different)
            $table->enum('status', ['for_sale', 'sold', 'pending', 'draft'])
                  ->default('draft')
                  ->charset('utf8mb4')
                  ->collation('utf8mb4_unicode_ci')
                  ->change();
        });
    }
};
