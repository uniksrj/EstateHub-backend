<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvestmentProfileRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'risk_appetite' => ['required', 'in:low,moderate,aggressive'],
            'budget_min' => ['nullable', 'numeric', 'min:0'],
            'budget_max' => ['nullable', 'numeric', 'gte:budget_min'],
            'target_cap_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'target_cash_on_cash' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'hold_period_years' => ['required', 'integer', 'min:1', 'max:50'],
            'preferred_cities' => ['nullable', 'array'],
            'preferred_cities.*' => ['string', 'max:100'],
            'preferred_property_types' => ['nullable', 'array'],
            'preferred_property_types.*' => ['string', 'max:100'],
        ];
    }
}