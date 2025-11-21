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
        Schema::create('deal_documents', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('deal_id')->constrained()->onDelete('cascade');
            $table->foreignId('property_offer_id')->constrained('property_offers')->onDelete('cascade');
            $table->foreignId('uploaded_by')->constrained('users');

            // External File Info
            $table->string('document_name');
            $table->text('file_url');
            $table->string('file_size')->nullable();
            $table->string('file_type');
            $table->string('external_file_id')->nullable();

            // Categorization
            $table->enum('category', [
                'contracts',
                'financial',
                'inspections',
                'mortgage',
                'appraisal',
                'insurance',
                'title_reports',
                'disclosures',
                'addendums',
                'closing_docs',
                'miscellaneous'
            ]);

            $table->enum('document_type', [
                // Contracts
                'purchase_agreement',
                'counter_offer',
                'addendum',

                // Financial
                'emd_receipt',
                'wire_instructions',
                'closing_disclosure',
                'commission_agreement',

                // Inspections
                'home_inspection',
                'pest_inspection',
                'roof_inspection',
                'foundation_inspection',

                // Mortgage
                'loan_application',
                'pre_approval_letter',
                'appraisal_report',
                'underwriting_approval',

                // Title & Legal
                'title_report',
                'deed',
                'property_disclosures',
                'hoa_documents',

                // Closing
                'closing_statement',
                'settlement_statement',
                'wire_confirmation'
            ]);

            $table->json('shared_with')->default('[]');
            $table->text('description')->nullable();
            $table->boolean('requires_signature')->default(false);
            $table->timestamp('signed_at')->nullable();

            $table->timestamps();

            // Index for better performance
            $table->index(['deal_id', 'property_offer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deal_documents');
    }
};
