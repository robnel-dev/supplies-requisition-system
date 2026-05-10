<?php

namespace App\Http\Requests\Admin;

class StoreUserRequest extends UserRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            ...$this->assignmentRules(),
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
