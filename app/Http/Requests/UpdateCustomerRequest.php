<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $customer = $this->route('customer');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes', 'nullable', 'email', 'max:255',
                Rule::unique('customers', 'email')->ignore($customer->id),
            ],
            'phone' => ['sometimes', 'nullable', 'string', 'regex:/^[0-9+\-\s()]{7,30}$/'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'The phone number may contain only digits, spaces and + - ( ), 7 to 30 characters.',
        ];
    }
}