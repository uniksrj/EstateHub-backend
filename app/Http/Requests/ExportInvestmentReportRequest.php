<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportInvestmentReportRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:portfolio,market,property'],
            'format' => ['required', 'in:csv,pdf'],
            'property_id' => ['required_if:type,property', 'nullable', 'exists:properties,id'],
            'city' => ['required_if:type,market', 'nullable', 'string', 'max:100'],
        ];
    }
}