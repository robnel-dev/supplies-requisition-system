<?php

namespace App\Services;

use App\Models\Department;
use App\Models\ExternalDepartmentReference;
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
        $attributes = $this->attributesFromRequest($data);
        $attributes['password'] = Hash::make($data['password']);
        $attributes['created_by'] = Auth::id();

        return User::create($attributes);
    }

    public function updateUser(User $user, array $data): bool
    {
        unset($data['password']);

        return $user->update($this->attributesFromRequest($data));
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

    private function attributesFromRequest(array $data): array
    {
        $department = Department::findOrFail($data['department_id']);
        $reference = $this->resolveReference($data['external_department_reference_id'] ?? null);

        if (! $reference && $department->type === 'head_office' && $department->external_department_reference_id) {
            $reference = $department->externalReference;
        }

        $costCenter = filled($data['cost_center'] ?? null)
            ? trim((string) $data['cost_center'])
            : ($reference?->cost_center ?? $department->cost_center);

        $accountName = $department->type === 'store' && $reference
            ? $reference->name
            : trim((string) $data['name']);

        return [
            'name' => $accountName,
            'email' => $data['email'],
            'role' => $data['role'],
            'department_id' => $department->id,
            'external_department_reference_id' => $reference?->id,
            'cost_center' => $costCenter,
        ];
    }

    private function resolveReference(null|int|string $referenceId): ?ExternalDepartmentReference
    {
        if (! $referenceId) {
            return null;
        }

        return ExternalDepartmentReference::active()->findOrFail($referenceId);
    }
}
