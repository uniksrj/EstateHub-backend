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
            $table->unsignedBigInteger('rejected_by')->nullable()->after('is_virtual');
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('property_tours', function (Blueprint $table) {
            $table->dropColumn(['rejected_by', 'rejected_at']);
        });
    }
};
