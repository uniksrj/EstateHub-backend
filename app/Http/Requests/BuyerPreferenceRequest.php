<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BuyerPreferenceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        // Ensure numeric fields are cast properly
        $this->merge([
            'min_price' => $this->numeric($this->min_price),
            'max_price' => $this->numeric($this->max_price),
            'min_bedrooms' => $this->numeric($this->min_bedrooms),
            'min_bathrooms' => $this->numeric($this->min_bathrooms),
            'min_sqft' => $this->numeric($this->min_sqft),
            'max_sqft' => $this->numeric($this->max_sqft),
        ]);
    }

    private function numeric($value)
    {
        return is_numeric($value) ? (float) $value : $value;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
         return [
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0|gt:min_price',
            'min_bedrooms' => 'nullable|integer|min:0',
            'min_bathrooms' => 'nullable|integer|min:0',
            'property_type' => 'nullable|string|in:house,condo,apartment,townhouse',
            'location' => 'nullable|string|max:255',
            'amenities' => 'nullable|array',
            'min_sqft' => 'nullable|integer|min:0',
            'max_sqft' => 'nullable|integer|min:0|gt:min_sqft',
            'alerts_enabled' => 'boolean',
            'alert_frequency' => 'in:instant,daily,weekly'
        ];
    }

     public function messages()
    {
        return [
            'max_price.gt' => 'Maximum price must be greater than minimum price.',
            'max_sqft.gt' => 'Maximum square footage must be greater than minimum.',
        ];
    }
}
