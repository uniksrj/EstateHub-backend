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
         Schema::table('users', function (Blueprint $table) {
            $table->tinyInteger('is_active')
                ->default(0)
                ->comment('1=active, 0=inactive, 3=pending, 4=suspend')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Revert to the original definition (adjust as needed)
            $table->tinyInteger('is_active')
                ->default(0)
                ->comment(null)
                ->change();
        });
    }
};
