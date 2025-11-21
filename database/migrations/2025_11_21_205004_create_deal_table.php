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
         Schema::create('deals', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys
            $table->foreignId('offer_id')->constrained('property_offers')->onDelete('cascade');
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('agent_id')->constrained('users')->onDelete('cascade');
            
            // Deal Details
            $table->decimal('final_price', 12, 2);
            $table->decimal('earnest_money_deposit', 10, 2)->nullable();
            
            // Status & Progress
            $table->enum('status', [
                'under_contract', 
                'pending_sale', 
                'closing', 
                'closed', 
                'cancelled',
                'fall_through'
            ])->default('under_contract');
            
            $table->enum('current_step', [
                'contract_generation',
                'earnest_money', 
                'inspection',
                'mortgage_processing',
                'appraisal',
                'insurance',
                'closing_preparation',
                'final_walkthrough',
                'closed'
            ])->default('contract_generation');
            
            $table->integer('progress_percentage')->default(0);
            
            // Dates
            $table->date('accepted_date');
            $table->date('expected_closing_date');
            $table->date('actual_closing_date')->nullable();
            $table->date('inspection_deadline')->nullable();
            $table->date('mortgage_deadline')->nullable();
            $table->date('appraisal_deadline')->nullable();
            
            // Additional Fields for Future
            $table->string('title_company')->nullable();
            $table->string('lender_name')->nullable();
            $table->string('inspection_company')->nullable();
            $table->text('special_terms')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for Performance
            $table->index(['status', 'current_step']);
            $table->index(['agent_id', 'status']);
            $table->index('expected_closing_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
