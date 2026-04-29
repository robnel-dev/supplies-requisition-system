<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Get the user ID from the route so we can ignore it in the unique email check
        $userId = $this->route('user')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $userId],
            'role' => ['required', 'in:hr_admin,approver,requestor'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'cost_center' => ['nullable', 'string', 'max:255'],
            // Password is NOT validated here because it has its own dedicated modal/route
        ];
    }
}