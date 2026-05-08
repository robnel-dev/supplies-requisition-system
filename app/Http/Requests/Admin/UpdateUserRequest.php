<?php

namespace App\Http\Requests\Admin;

use App\Models\Department;
use App\Models\ExternalDepartmentReference;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
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
                if ($reference->branch !== 'Store' || $reference->area !== $department->area) {
                    $validator->errors()->add(
                        'external_department_reference_id',
                        'The selected store does not belong to the selected department area.'
                    );
                }

                return;
            }

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
        });
    }
}
