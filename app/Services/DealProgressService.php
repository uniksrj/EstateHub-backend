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

    public function getStepDeadline(string $currentStep, Carbon $acceptedDate,  int $extensions = 0,  Carbon $lastExtendedDate = null): string
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
        if ($lastExtendedDate) {
            return $lastExtendedDate->format('Y-m-d');
        }

        if ($extensions > 0) {
            $deadline->addDays($extensions * 3);
        }

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
            'mortgage_processing' => 'closing_preparation',
            'closed' => 'closed'
        ];

        return $steps[$currentStep] ?? 'closed';
    }

    public function getRequiredDocumentsForStep($stepKey)
    {
        $stepDocuments = [
            'contract_generation' => ['purchase_agreement', 'counter_offer', 'property_disclosures'],
            'earnest_money' => ['emd_receipt', 'wire_instructions', 'funds_verification'],
            'inspection' => ['home_inspection', 'pest_inspection'],
            'mortgage_processing' => ['loan_application', 'underwriting_approval', 'appraisal_report', 'title_report'],
            'closed' => ['closing_disclosure', 'settlement_statement', 'deed', 'wire_confirmation']
        ];

        return $stepDocuments[$stepKey] ?? [];
    }

    public function getDeadlineStatus(
        string $currentStep,
        Carbon $acceptedDate,
        int $deadlineExtensions = 0,
        ?Carbon $lastExtendedDate = null
    ): string {
        $today = Carbon::today();

        if ($lastExtendedDate) {
            $deadlineDate = $lastExtendedDate;
        } else {
            $deadlineDate = $this->calculateDeadline($currentStep, $acceptedDate, $deadlineExtensions);
        }

        if ($today->greaterThan($deadlineDate)) {
            return 'missed';
        }

        if ($deadlineExtensions > 0) {
            return 'extended';
        }

        return 'on_track';
    }

    private function calculateDeadline($currentStep, $acceptedDate, $extensions)
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

        if ($extensions > 0) {
            $deadline->addDays($extensions * 3);
        }

        return $deadline;
    }

    public function getStepFromDocumentName($documentType)
    {
        $documentStepMap = [
            'purchase_agreement' => 'contract_generation',
            'counter_offer' => 'contract_generation',
            'emd_receipt' => 'earnest_money',
            'wire_instructions' => 'earnest_money',
            'funds_verification' => 'earnest_money',
            'home_inspection' => 'inspection',
            'pest_inspection' => 'inspection',
            'loan_application' => 'mortgage_processing',
            'appraisal_report' => 'mortgage_processing',
            'underwriting_approval' => 'mortgage_processing',
            'appraisal_report' => 'appraisal',
            'title_report' => 'mortgage_processing',
            'deed' => 'closed',
            'property_disclosures' => 'contract_generation',
            'settlement_statement' => 'closed',
            'wire_confirmation' => 'closed',
            'commission_agreement' => 'closed',
            'roof_inspection' => 'inspection',
            'foundation_inspection' => 'inspection',
            'addendum' => 'contract_generation',
            'repair_addendum' => 'inspection',
            'closing_disclosure' => 'closing_preparation',
            'settlement_statement' => 'closing_preparation',
            'deed' => 'closing_preparation',
            'wire_confirmation' => 'closing_preparation',
        ];

        return $documentStepMap[$documentType] ?? null;
    }
}
