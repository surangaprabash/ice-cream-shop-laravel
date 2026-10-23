<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * If only the category changes, still check the existing name
     * against the new category.
     */
    protected function prepareForValidation(): void
    {
        $item = $this->route('item');

        if ($this->has('category_id') && ! $this->has('name')) {
            $this->merge(['name' => $item->name]);
        }
    }

    public function rules(): array
    {
        $item = $this->route('item');
        $categoryId = $this->input('category_id', $item->category_id);

        return [
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'name' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('items', 'name')
                    ->where('category_id', $categoryId)
                    ->ignore($item->id),
            ],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0', 'max:999999.99'],
            'quantity' => ['sometimes', 'required', 'integer', 'min:0'],
            'unit' => ['sometimes', 'required', 'string', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'An item with this name already exists in the selected category.',
        ];
    }
}