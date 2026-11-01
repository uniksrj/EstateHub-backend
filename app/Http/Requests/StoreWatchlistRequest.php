<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWatchlistRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'property_id' => ['required', 'exists:properties,id'],
            'alert_min_cap_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'alert_max_price' => ['nullable', 'numeric', 'min:0'],
            'notify_on_price_drop' => ['nullable', 'boolean'],
        ];
    }
}