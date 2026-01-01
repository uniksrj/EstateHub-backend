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
        Schema::table('deals', function (Blueprint $table) {
            $table->string('earnest_currency', 3)->default('USD')->after('earnest_money_deposit');

            // Payment Details
            $table->enum('earnest_payment_method', ['wire', 'check', 'cash', 'other'])->nullable();
            $table->string('earnest_payment_reference', 100)->nullable();

            // Due Dates
            $table->date('earnest_due_date')->nullable();
            $table->date('earnest_received_date')->nullable();

            // Status
            $table->enum('earnest_status', ['pending', 'received', 'cleared', 'forfeited'])->default('pending');

            // Hold Details
            $table->string('earnest_held_by', 100)->nullable()->comment('Escrow company name');
            $table->text('earnest_notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropColumn([
                'earnest_currency',
                'earnest_payment_method',
                'earnest_payment_reference',
                'earnest_due_date',
                'earnest_received_date',
                'earnest_status',
                'earnest_held_by',
                'earnest_notes'
            ]);
        });
    }
};
