<?php

namespace App\Http\Requests\Admin;

use App\Models\Department;
use App\Models\ExternalDepartmentReference;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function assignmentRules(): array
    {
        return [
            'role' => ['required', 'in:hr_admin,approver,requestor'],
            'department_id' => ['required', 'exists:departments,id'],
            'external_department_reference_id' => [
                'nullable',
                'integer',
                Rule::exists('external_department_references', 'id')
                    ->where(fn ($query) => $query->where('active', true)),
            ],
            'cost_center' => ['required', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $department = Department::find($this->input('department_id'));
            $referenceId = $this->input('external_department_reference_id');

            if (! $department || ! $referenceId) {
                return;
            }

            $reference = ExternalDepartmentReference::active()->find($referenceId);

            if (! $reference) {
                return;
            }

            if ($department->type === 'store') {
                $this->validateStoreReference($validator, $department, $reference);
                return;
            }

            $this->validateHeadOfficeReference($validator, $department, $reference);
        });
    }

    private function validateStoreReference($validator, Department $department, ExternalDepartmentReference $reference): void
    {
        // Store users must be tied to a store inside the selected area bucket.
        if ($reference->branch === 'Store' && $reference->area === $department->area) {
            return;
        }

        $validator->errors()->add(
            'external_department_reference_id',
            'The selected store does not belong to the selected department area.'
        );
    }

    private function validateHeadOfficeReference($validator, Department $department, ExternalDepartmentReference $reference): void
    {
        if ($reference->branch !== 'Head Office') {
            $validator->errors()->add(
                'external_department_reference_id',
                'The selected reference must be a Head Office reference.'
            );

            return;
        }

        if ($department->external_department_reference_id && (int) $department->external_department_reference_id !== (int) $reference->id) {
            $validator->errors()->add(
                'external_department_reference_id',
                'The selected reference does not match the selected Head Office department.'
            );
        }
    }
}
