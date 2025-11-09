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
        Schema::table('buyer_preferences', function (Blueprint $table) {
            $table->string('name'); 
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('buyer_preferences', function (Blueprint $table) {
            $table->dropColumn(['name', 'is_active', 'is_default', 'deleted_at']);
        });
    }
};
