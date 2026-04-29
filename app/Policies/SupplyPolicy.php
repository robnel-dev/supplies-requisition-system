<?php

namespace App\Policies;

use App\Models\Supply;
use App\Models\User;

class SupplyPolicy
{
    // A helper method to check if the user is an HR Admin
    private function isHrAdmin(User $user): bool
    {
        return $user->role === 'hr_admin'; // Adjust string based on exact DB enum
    }

    public function viewAny(User $user): bool
    {
        return $this->isHrAdmin($user);
    }
    public function create(User $user): bool
    {
        return $this->isHrAdmin($user);
    }
    public function update(User $user, Supply $supply): bool
    {
        return $this->isHrAdmin($user);
    }
    public function delete(User $user, Supply $supply): bool
    {
        return $this->isHrAdmin($user);
    }
}
