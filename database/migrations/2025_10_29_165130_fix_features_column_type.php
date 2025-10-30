<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('UPDATE properties SET features = NULL WHERE LENGTH(features) > 255 OR features IS NOT NULL');
        
        // Then change the column type to TEXT and nullable
        Schema::table('properties', function (Blueprint $table) {
            $table->text('features')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('features', 255)->nullable()->change();
        });
    }
};
