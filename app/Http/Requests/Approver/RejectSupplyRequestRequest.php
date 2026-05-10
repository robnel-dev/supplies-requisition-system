<?php

namespace App\Http\Requests\Approver;

use Illuminate\Foundation\Http\FormRequest;

class RejectSupplyRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'rejection_reason' => trim((string) $this->input('rejection_reason')),
        ]);
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
