<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'hr_admin';
    }


    public function view(User $user, User $model): bool
    {
        return $user->role === 'hr_admin';
    }


    public function create(User $user): bool
    {
        return $user->role === 'hr_admin';
    }


    public function update(User $user, User $model): bool
    {
        // HR Admins can update users. 
        // (Later, we can add logic here if users are allowed to edit their own profiles: $user->id === $model->id)
        return $user->role === 'hr_admin';
    }

    /**
     * Determine whether the user can delete/disable the model.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->role === 'hr_admin';
    }
}
