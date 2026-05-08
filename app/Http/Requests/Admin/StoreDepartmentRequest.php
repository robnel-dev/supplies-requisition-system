<?php

namespace App\Http\Requests\Admin;

use App\Models\ExternalDepartmentReference;
use App\Services\DepartmentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'hr_admin';
    }

    protected function prepareForValidation(): void
    {
        $updates = [];

        if ($this->filled('ext_ref_id') && ! $this->filled('external_department_reference_id')) {
            $updates['external_department_reference_id'] = $this->input('ext_ref_id');
        }

        if ($this->filled('branch') && ! $this->filled('type')) {
            $updates['type'] = $this->input('branch') === 'Head Office' ? 'head_office' : 'store';
        }

        if (($this->input('type') === 'store') && array_key_exists((string) $this->input('area'), DepartmentService::STORE_AREAS)) {
            $area = DepartmentService::STORE_AREAS[$this->input('area')];

            $updates['external_department_reference_id'] = null;
            $updates['code'] = $area['code'];
            $updates['name'] = $area['name'];
            $updates['cost_center'] = null;
            $updates['cost_center_source'] = 'manual';
        }

        if ($updates !== []) {
            $this->merge($updates);
        }
    }

    public function rules(): array
    {
        $departmentId = $this->route('department')?->id;

        return [
            'external_department_reference_id' => [
                'nullable',
                'integer',
                Rule::exists('external_department_references', 'id')
                    ->where(fn ($query) => $query->where('active', true)),
            ],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('departments', 'code')->ignore($departmentId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['head_office', 'store'])],
            'area' => [
                Rule::requiredIf($this->input('type') === 'store'),
                'nullable',
                'string',
                'max:20',
            ],
            'cost_center' => [Rule::requiredIf($this->input('type') === 'head_office'), 'nullable', 'string', 'max:20'],
            'cost_center_source' => ['nullable', Rule::in(['external', 'manual'])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $referenceId = $this->input('external_department_reference_id');

            if ($this->input('type') === 'store' && ! array_key_exists((string) $this->input('area'), DepartmentService::STORE_AREAS)) {
                $validator->errors()->add('area', 'Please select one of the configured store areas.');
            }

            if (! $referenceId) {
                return;
            }

            if ($this->input('type') === 'store') {
                $validator->errors()->add(
                    'external_department_reference_id',
                    'Store departments are managed by area only. Select the specific store in User Management.'
                );

                return;
            }

            $reference = ExternalDepartmentReference::active()->find($referenceId);

            if (! $reference) {
                return;
            }

            if ($reference->departmentType() !== $this->input('type')) {
                $validator->errors()->add(
                    'external_department_reference_id',
                    'The selected external reference does not match the selected department type.'
                );
            }

            if ($this->input('code') !== $reference->department_code) {
                $validator->errors()->add(
                    'code',
                    'The department code must match the selected external reference.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'cost_center.required' => 'Cost center is required.',
            'name.required'        => 'Department name is required.',
            'code.required'        => 'Department code is required.',
        ];
    }
}
