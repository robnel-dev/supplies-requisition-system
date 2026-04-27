<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    /**
     * Note: In Laravel 11/12, policies are auto-discovered 
     * if they follow the Naming Convention (Model + Policy).
     */

    // Only HR Admins can view the list or manage departments
    public function viewAny(User $user): bool
    {
        return $user->role === 'hr_admin';
    }

    public function create(User $user): bool
    {
        return $user->role === 'hr_admin';
    }

    public function update(User $user, Department $department): bool
    {
        return $user->role === 'hr_admin';
    }

    public function delete(User $user, Department $department): bool
    {
        return $user->role === 'hr_admin';
    }
}