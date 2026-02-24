<?php

namespace App\Http\Controllers;

use App\Models\LoanApplications;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Loan extends Controller
{
    public function __construct() {}

    public function save_property_loan_details(Request $request)
    {
        DB::beginTransaction();
        try {            
            $validatedData = $request->validate([                
                'annualIncome' => 'required|numeric',
                'bankruptcyExplanation' => 'nullable|string',
                'creditScore' => 'nullable|string', 
                'dateOfBirth' => 'required|date',
                'downPayment' => 'required|numeric',
                'email' => 'required|email',
                'firstName' => 'required|string',
                'lastName' => 'required|string',
                'employerName' => 'required|string',
                'employmentStatus' => 'required|string',
                'hasBankruptcies' => 'required|boolean',
                'jobTitle' => 'nullable|string',
                'occupancyType' => 'required|string',
                'phone' => 'required|string',
                'propertyAddress' => 'required|string',
                'propertyPrice' => 'required|numeric',
                'propertyType' => 'required|string',
                'ssn' => 'required|string',
                'yearsAtJob' => 'nullable|integer',

                // IDs
                'offerId' => 'required|integer|exists:property_offers,id',
                'propertyId' => 'required|integer|exists:properties,id',

                // Loan details (from selectedLoan)
                'selectedLoan.loanAmount' => 'required|numeric',
                'selectedLoan.rate' => 'required|numeric',
                'selectedLoan.type' => 'required|string',
                'selectedLoan.term' => 'required|integer',
                'selectedLoan.monthlyPI' => 'required|numeric',
            ]);
            
            $creditScoreMap = [
                'excellent' => 1,
                'good' => 2,
                'fair' => 3,
                'poor' => 4,
            ];
            $creditScoreInt = $creditScoreMap[$validatedData['creditScore']] ?? null;
            
            $downPaymentPercent = ($validatedData['downPayment'] / $validatedData['propertyPrice']) * 100;

            $applicationNumber = 'LOAN-' . strtoupper(uniqid());
            
            $selectedLoanDetails = $request->input('selectedLoan'); 
            $formData = $request->except(['selectedLoan', 'documents', 'ssn']);
            $documentsStatus = $request->input('documents', []);
            
            $property = Property::find($validatedData['propertyId']);
            $propertySnapshot = $property ? $property->toArray() : null;
            
            $loanApplication = LoanApplications::create([
                'user_id' => auth()->id(),
                'offer_id' => $validatedData['offerId'],
                'property_id' => $validatedData['propertyId'],
                'application_number' => $applicationNumber,
                'status' => 'pending',
                
                'loan_type' => $validatedData['selectedLoan']['type'],
                'loan_amount' => $validatedData['selectedLoan']['loanAmount'],
                'interest_rate' => $validatedData['selectedLoan']['rate'],
                'loan_term' => $validatedData['selectedLoan']['term'],
                'monthly_payment' => $validatedData['selectedLoan']['monthlyPI'],
                'down_payment' => $validatedData['downPayment'],
                'down_payment_percentage' => $downPaymentPercent,
                
                'property_address' => $validatedData['propertyAddress'],
                'property_city' => null,
                'property_state' => null,
                'property_zip' => null,
                'property_type' => $validatedData['propertyType'],
                'occupancy_type' => $validatedData['occupancyType'],
                'property_price' => $validatedData['propertyPrice'],
                
                'first_name' => $validatedData['firstName'],
                'last_name' => $validatedData['lastName'],
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'],
                'date_of_birth' => $validatedData['dateOfBirth'],
                'ssn_last_four' => substr($validatedData['ssn'], -4),
                
                'employment_status' => $validatedData['employmentStatus'],
                'employer_name' => $validatedData['employerName'],
                'job_title' => $validatedData['jobTitle'],
                'years_at_job' => $validatedData['yearsAtJob'] ?? null,
                'annual_income' => $validatedData['annualIncome'],
                
                'credit_score_range' => $creditScoreInt,
                'has_bankruptcies' => $validatedData['hasBankruptcies'],
                'bankruptcy_explanation' => $validatedData['bankruptcyExplanation'],
                
                'selected_loan_details' => $selectedLoanDetails,
                'form_data' => $formData,
                'documents_status' => $documentsStatus,
                'property_details_snapshot' => $propertySnapshot,
                
                'submitted_at' => now(),
                'last_update_at' => now(),
                'timeline' => [
                    [
                        'date' => now()->toISOString(),
                        'status' => 'pending',
                        'description' => 'Application submitted'
                    ]
                ],
            ]);

            // Log user activity
            $loanApplication->create_user_activity($request, $loanApplication, 'Loan Application Created');

            DB::commit();

            return response()->json([
                'message' => 'Loan application submitted successfully',
                'application' => $loanApplication,
                'success' => true
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to save loan application details',
                'error_type' => class_basename($th),
                'error_message' => $th->getMessage(),
            ], 500);
        }
    }
}
