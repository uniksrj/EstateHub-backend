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
        Schema::create('loan_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Link to Offer and Property
            $table->foreignId('offer_id')->nullable()->constrained('property_offers')->onDelete('set null');
            $table->foreignId('property_id')->nullable()->constrained('properties')->onDelete('set null');
            
            // Application Reference
            $table->string('application_number')->unique();
            $table->string('status')->default('pending');
            
            // Loan Details
            $table->string('loan_type');
            $table->decimal('loan_amount', 15, 2);
            $table->decimal('interest_rate', 5, 2);
            $table->integer('loan_term');
            $table->decimal('monthly_payment', 15, 2);
            $table->decimal('down_payment', 15, 2);
            $table->decimal('down_payment_percentage', 5, 2);
            
            // Property Details (flat columns for quick access)
            $table->string('property_address')->nullable();
            $table->string('property_city')->nullable();
            $table->string('property_state')->nullable();
            $table->string('property_zip')->nullable();
            $table->string('property_type')->nullable();
            $table->string('occupancy_type');
            $table->decimal('property_price', 15, 2)->nullable();
            
            // Personal Information
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone');
            $table->date('date_of_birth');
            $table->string('ssn_last_four', 4)->nullable();
            
            // Employment Information
            $table->string('employment_status');
            $table->string('employer_name')->nullable();
            $table->string('job_title')->nullable();
            $table->integer('years_at_job')->nullable();
            $table->decimal('annual_income', 15, 2);
            
            // Financial Information
            $table->integer('credit_score_range')->nullable(); // e.g., 1=Excellent, 2=Good, etc.
            $table->boolean('has_bankruptcies')->default(false);
            $table->text('bankruptcy_explanation')->nullable();
            
            // Additional JSON Data (backup/extended info)
            $table->json('selected_loan_details')->nullable();     // Full loan object with amortization
            $table->json('form_data')->nullable();                 // Raw form submission
            $table->json('documents_status')->nullable();          // Track uploaded docs
            $table->json('property_details_snapshot')->nullable(); // Snapshot of property at application time
            $table->json('timeline')->nullable();                  // Status history
            
            // Timestamps
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('last_update_at')->nullable();
            $table->timestamp('expected_decision_date')->nullable();
            
            // Admin/Officer Fields
            $table->foreignId('assigned_officer_id')->nullable()->constrained('users');
            $table->text('officer_notes')->nullable();
            $table->json('approval_details')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('offer_id');
            $table->index('property_id');
            $table->index('user_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_applications');
    }
};
