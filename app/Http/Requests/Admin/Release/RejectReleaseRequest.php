<?php

namespace App\Http\Requests\Admin\Release;

use App\Models\SupplyRequest;
use Illuminate\Foundation\Http\FormRequest;

class RejectReleaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $supplyRequest = $this->route('supplyRequest');

        return $this->user()?->role === 'hr_admin'
            && $supplyRequest instanceof SupplyRequest
            && $supplyRequest->status === SupplyRequest::STATUS_APPROVED;
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
