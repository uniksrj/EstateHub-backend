<?php

namespace App\Services;

use App\Models\Deal;
use Carbon\Carbon;

class DealProgressService
{
    public function getNextStepDescription(string $currentStep): string
    {
        $steps = [
            'contract_generation' => 'Review Purchase Agreement',
            'earnest_money' => 'Confirm EMD Receipt',
            'inspection' => 'Schedule Home Inspection',
            'mortgage_processing' => 'Submit Loan Documentation',
            'appraisal' => 'Review Appraisal Report',
            'closing_preparation' => 'Schedule Closing Date',
            'final_walkthrough' => 'Complete Final Walkthrough',
            'closed' => 'Deal Completed'
        ];

        return $steps[$currentStep] ?? 'Next Step';
    }

    public function getStepDeadline(string $currentStep, Carbon $acceptedDate): string
    {
        $deadlines = [
            'contract_generation' => $acceptedDate->copy()->addDays(3),
            'earnest_money' => $acceptedDate->copy()->addDays(5),
            'inspection' => $acceptedDate->copy()->addDays(10),
            'mortgage_processing' => $acceptedDate->copy()->addDays(30),
            'appraisal' => $acceptedDate->copy()->addDays(20),
            'closing_preparation' => $acceptedDate->copy()->addDays(40),
            'final_walkthrough' => $acceptedDate->copy()->addDays(44),
        ];

        $deadline = $deadlines[$currentStep] ?? $acceptedDate->copy()->addDays(45);
        return $deadline->format('Y-m-d');
    }

    public function getPriority(string $currentStep, Carbon $acceptedDate): string
    {
        $today = now();
        $deadline = Carbon::createFromFormat('Y-m-d', $this->getStepDeadline($currentStep, $acceptedDate));
        $daysUntilDeadline = $today->diffInDays($deadline, false);

        if ($daysUntilDeadline <= 2) {
            return 'high';
        } elseif ($daysUntilDeadline <= 5) {
            return 'medium';
        }

        return 'low';
    }

    // Bonus: You can add more reusable methods
    public function calculateProgress(string $currentStep): int
    {
        $progressMap = [
            'contract_generation' => 20,
            'earnest_money' => 40,
            'inspection' => 60,
            'mortgage_processing' => 70,
            'appraisal' => 80,
            'closing_preparation' => 90,
            'final_walkthrough' => 95,
            'closed' => 100
        ];

        return $progressMap[$currentStep] ?? 0;
    }

    public function getCategoryFromDocumentType($documentType)
    {
        $categoryMap = [
            // Contracts
            'purchase_agreement' => 'contracts',
            'counter_offer' => 'contracts',

            // Financial
            'emd_receipt' => 'financial',
            'wire_instructions' => 'financial',
            'funds_verification' => 'financial',
            'closing_disclosure' => 'financial',
            'commission_agreement' => 'financial',

            // Inspections
            'home_inspection' => 'inspections',
            'pest_inspection' => 'inspections',
            'roof_inspection' => 'inspections',
            'foundation_inspection' => 'inspections',

            // Mortgage
            'loan_application' => 'mortgage',
            'pre_approval_letter' => 'mortgage',
            'underwriting_approval' => 'mortgage',

            // Appraisal
            'appraisal_report' => 'appraisal',

            // Title & Legal
            'title_report' => 'title_reports',
            'deed' => 'title_reports',
            'property_disclosures' => 'disclosures',
            'hoa_documents' => 'disclosures',

            // Closing
            'settlement_statement' => 'closing_docs',
            'wire_confirmation' => 'closing_docs',

            // Addendums
            'addendum' => 'addendums',
            'repair_addendum' => 'addendums',
        ];

        return $categoryMap[$documentType] ?? 'miscellaneous';
    }

    public function getNextStep($currentStep)
    {
        $steps = [
            'contract_generation' => 'earnest_money',
            'earnest_money' => 'inspection',
            'inspection' => 'mortgage_processing',
            'mortgage' => 'closing_preparation',
            'closing' => 'closed'
        ];

        return $steps[$currentStep] ?? 'closed';
    }

    public function getRequiredDocumentsForStep($stepKey)
    {
        $stepDocuments = [
            'contract_generation' => ['purchase_agreement', 'counter_offer', 'property_disclosures'],
            'earnest_money' => ['emd_receipt', 'wire_instructions', 'funds_verification'],
            'inspection' => ['home_inspection', 'pest_inspection'],
            'mortgage' => ['loan_application', 'underwriting_approval', 'appraisal_report', 'title_report'],
            'closing' => ['closing_disclosure', 'settlement_statement', 'deed', 'wire_confirmation']
        ];

        return $stepDocuments[$stepKey] ?? [];
    }
}
