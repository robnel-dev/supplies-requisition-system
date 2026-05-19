<?php

namespace App\Http\Requests\Admin\Release;

use Illuminate\Foundation\Http\FormRequest;

class RejectReleaseRequest extends FormRequest
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
