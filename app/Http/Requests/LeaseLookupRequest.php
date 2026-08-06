<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeaseLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dealer_code' => ['required', 'string', 'max:32', 'regex:/^[A-Za-z0-9\-]+$/'],
            'zip'         => ['required', 'string', 'regex:/^\d{5}(-\d{4})?$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'dealer_code.required' => 'Please enter your dealer code.',
            'dealer_code.regex'    => 'Dealer code may contain letters, numbers and dashes only.',
            'zip.required'         => 'Please enter a ZIP code.',
            'zip.regex'            => 'Enter a valid US ZIP code (e.g. 80229 or 80229-1234).',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'dealer_code' => trim((string) $this->input('dealer_code')),
            'zip'         => trim((string) $this->input('zip')),
        ]);
    }
}
