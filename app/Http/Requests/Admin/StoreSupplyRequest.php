<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_code' => ['required', 'string', 'max:50', 'unique:supplies,item_code'],
            'item_description' => ['required', 'string', 'max:500'],
            'category' => ['required', 'string', 'max:100'],
            'unit' => ['required', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
