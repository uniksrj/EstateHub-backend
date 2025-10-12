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
         Schema::create('property_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
            $table->decimal('offer_amount', 15, 2);
            $table->text('message')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected', 'countered', 'expired'])->default('pending');
            $table->decimal('counter_offer_amount', 15, 2)->nullable();
            $table->text('counter_offer_message')->nullable();
            $table->decimal('commission_rate', 5, 2)->default(2.5); // Default 2.5% commission
            $table->text('special_conditions')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('offer_date');
            $table->dateTime('accepted_at')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->timestamps();

            // Indexes for better performance
            $table->index(['property_id', 'status']);
            $table->index(['buyer_id', 'status']);
            $table->index(['status', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_offers');
    }
};
