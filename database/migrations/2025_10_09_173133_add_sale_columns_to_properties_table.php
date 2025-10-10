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
            $table->timestamp('sold_at')->nullable()->after('updated_at');
            $table->unsignedBigInteger('buyer_id')->nullable()->after('sold_at');
            $table->decimal('sale_price', 15, 2)->nullable()->after('buyer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['sold_at', 'buyer_id', 'sale_price']);
        });
    }
};
