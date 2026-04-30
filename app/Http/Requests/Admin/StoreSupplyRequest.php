<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSupplyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'item_code' => ['required', 'string', 'unique:supplies,item_code'],
            'item_description' => ['required', 'string'], 
            'category' => ['required', 'string', 'max:100'],
            'unit' => ['required', 'string', 'max:50'],
        ];
    }
}
