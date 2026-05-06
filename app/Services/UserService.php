<?php

namespace App\Services;

use App\Models\User;
use App\Models\SupplyRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function createUser(array $data): User
    {
        $data['password'] = Hash::make($data['password']);
        $data['created_by'] = Auth::id();

        return User::create($data);
    }

    public function updateUser(User $user, array $data): bool
    {
        unset($data['password']);
        return $user->update($data);
    }

    public function updatePassword(User $user, string $newPassword): bool
    {
        return $user->update([
            'password' => Hash::make($newPassword)
        ]);
    }

    public function deleteUser(User $user): void
    {
        // Safety Check 1: Prevent the admin from deleting their own account
        if ($user->id === Auth::id()) {
            throw ValidationException::withMessages([
                'delete' => 'You cannot delete your own active session.'
            ]);
        }

        // Safety Check 2: Prevent deletion if they have active supply requests
        if (Schema::hasTable('supply_requests')) {
            $hasActiveRequests = SupplyRequest::where('user_id', $user->id)
                ->whereNotIn('status', ['draft', 'rejected', 'cancelled', 'released'])
                ->exists();

            if ($hasActiveRequests) {
                throw ValidationException::withMessages([
                    'delete' => 'Cannot delete this user because they have active requests.'
                ]);
            }
        }

        $user->delete();
    }
}
