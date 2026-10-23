<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('items', 'name')->where('category_id', $this->input('category_id')),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'quantity' => ['required', 'integer', 'min:0'],
            'unit' => ['sometimes', 'string', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'An item with this name already exists in the selected category.',
        ];
    }
}