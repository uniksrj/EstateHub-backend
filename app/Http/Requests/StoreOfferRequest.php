<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOfferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'property_id' => 'required|exists:properties,id',
            'buyer_id' => 'required|exists:users,id',
            'offer_amount' => 'required|numeric|min:1',
            'message' => 'nullable|string|max:2000',
            'status' => 'required|in:pending,accepted,rejected,countered,withdrawn',
            'counter_offer_amount' => 'nullable|numeric|min:0',
            'counter_offer_message' => 'nullable|string|max:2000',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'special_conditions' => 'required|json',
            'expires_at' => 'required|date|after_or_equal:now',
            'offer_date' => 'required|date',
            'accepted_at' => 'nullable|date',
            'rejected_at' => 'nullable|date',
        ];
    }

    public function messages()
    {
        return [
            'property_id.required' => 'Property ID is required.',
            'property_id.exists' => 'The selected property does not exist.',

            'buyer_id.required' => 'Buyer ID is required.',
            'buyer_id.exists' => 'The selected buyer does not exist.',

            'offer_amount.required' => 'Offer amount is required.',
            'offer_amount.numeric' => 'Offer amount must be a number.',
            'offer_amount.min' => 'Offer amount must be at least 1.',

            'message.string' => 'Message must be a string.',
            'message.max' => 'Message cannot exceed 2000 characters.',

            'status.required' => 'Status is required.',
            'status.in' => 'Status must be one of the following: pending, accepted, rejected, countered, withdrawn.',

            'counter_offer_amount.numeric' => 'Counter offer amount must be a number.',
            'counter_offer_amount.min' => 'Counter offer amount must be at least 0.',

            'counter_offer_message.string' => 'Counter offer message must be a string.',
            'counter_offer_message.max' => 'Counter offer message cannot exceed 2000 characters.',

            'commission_rate.required' => 'Commission rate is required.',
            'commission_rate.numeric' => 'Commission rate must be a number.',
            'commission_rate.min' => 'Commission rate cannot be less than 0%.',
            'commission_rate.max' => 'Commission rate cannot exceed 100%.',

            'special_conditions.required' => 'Special conditions are required.',
            'special_conditions.json' => 'Special conditions must be valid JSON format.',

            'expires_at.required' => 'Expiration date is required.',
            'expires_at.date' => 'Expiration date must be a valid date.',
            'expires_at.after_or_equal' => 'Expiration date must be today or in the future.',

            'offer_date.required' => 'Offer date is required.',
            'offer_date.date' => 'Offer date must be a valid date.',

            'accepted_at.date' => 'Accepted date must be a valid date.',
            'rejected_at.date' => 'Rejected date must be a valid date.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->has('special_conditions')) {
                $specialConditions = json_decode($this->special_conditions, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $validator->errors()->add('special_conditions', 'Invalid JSON format in special conditions.');
                }
            }
        });
    }
}
