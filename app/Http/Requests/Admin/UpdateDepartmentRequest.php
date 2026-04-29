<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Controlled by Policy/Middleware
    }

    public function rules(): array
    {
        // Get the department ID from the route for the unique ignore rule
        $departmentId = $this->route('department')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:departments,code,' . $departmentId],
            'type' => ['required', 'in:head_office,store'],
        ];
    }
}