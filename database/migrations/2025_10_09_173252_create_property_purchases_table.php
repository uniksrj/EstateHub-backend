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
        Schema::create('property_purchases', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('property_id')->nullable();
            $table->unsignedBigInteger('buyer_id')->nullable();
            $table->decimal('sale_price', 15, 2)->nullable();
            $table->decimal('commission', 10, 2)->nullable();
            $table->decimal('taxes', 10, 2)->nullable();
            $table->decimal('fees', 10, 2)->nullable();
            $table->decimal('net_amount', 15, 2)->nullable();
            $table->date('purchase_date')->nullable();
            $table->enum('status', ['completed', 'pending', 'cancelled'])->default('pending');
            $table->timestamp('created_at')->useCurrent();

            // Optional: add indexes / foreign keys if needed
            $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');
            $table->foreign('buyer_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_purchases');
    }
};
