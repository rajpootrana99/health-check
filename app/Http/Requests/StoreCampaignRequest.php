<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth handled at route level in Step 11
    }

    public function rules(): array
    {
        return [
            'business_type' => ['required', 'string', 'min:2', 'max:100'],
            'location'      => ['required', 'string', 'min:2', 'max:150'],
            'notes'         => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'business_type.required' => 'Please enter a business type (e.g. Dentists, Plumbers).',
            'location.required'      => 'Please enter a location (e.g. London, Dubai).',
        ];
    }
}
