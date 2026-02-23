<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Loan extends Controller
{
    public function __construct(){}

    public function save_property_loan_details(Request $request, $property_id)
    {
        $validatedData = $request->validate([
            'loan_amount' => 'required|numeric',
            'interest_rate' => 'required|numeric',
            'loan_term' => 'required|integer',
            'loan_type' => 'required|string',
        ]);

        // Assuming you have a Property model and a LoanDetail model
        $property = Property::find($property_id);
        if (!$property) {
            return response()->json(['message' => 'Property not found'], 404);
        }

        $loanDetail = new LoanDetail();
        $loanDetail->property_id = $property_id;
        $loanDetail->loan_amount = $validatedData['loan_amount'];
        $loanDetail->interest_rate = $validatedData['interest_rate'];
        $loanDetail->loan_term = $validatedData['loan_term'];
        $loanDetail->loan_type = $validatedData['loan_type'];
        $loanDetail->save();

        return response()->json(['message' => 'Loan details saved successfully', 'data' => $loanDetail], 201);
    }
    
}
