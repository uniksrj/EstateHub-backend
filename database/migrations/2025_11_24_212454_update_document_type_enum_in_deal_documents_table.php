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
        Schema::table('deal_documents', function (Blueprint $table) {
            $table->enum('document_type', [
                'purchase_agreement',
                'counter_offer',
                'addendum',
                'emd_receipt',
                'wire_instructions',
                'closing_disclosure',
                'commission_agreement',
                'home_inspection',
                'pest_inspection',
                'roof_inspection',
                'foundation_inspection',
                'loan_application',
                'pre_approval_letter',
                'appraisal_report',
                'underwriting_approval',
                'title_report',
                'deed',
                'property_disclosures',
                'hoa_documents',
                'closing_statement',
                'settlement_statement',
                'wire_confirmation',
                'funds_verification',
                'repair_addendum'
            ])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deal_documents', function (Blueprint $table) {
            $table->enum('document_type', [
                'purchase_agreement',
                'counter_offer',
                'addendum',
                'emd_receipt',
                'wire_instructions',
                'closing_disclosure',
                'commission_agreement',
                'home_inspection',
                'pest_inspection',
                'roof_inspection',
                'foundation_inspection',
                'loan_application',
                'pre_approval_letter',
                'appraisal_report',
                'underwriting_approval',
                'title_report',
                'deed',
                'property_disclosures',
                'hoa_documents',
                'closing_statement',
                'settlement_statement',
                'wire_confirmation'
            ])->change();
        });
    }
};
