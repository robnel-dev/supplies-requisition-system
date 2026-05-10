<?php

namespace App\Policies;

use App\Models\Supply;
use App\Models\User;

class SupplyPolicy
{
    private function isHrAdmin(User $user): bool
    {
        return $user->role === 'hr_admin';
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
