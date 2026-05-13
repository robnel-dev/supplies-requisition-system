<?php

namespace App\Policies;

use App\Models\SupplyRequest;
use App\Models\User;

class SupplyRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['hr_admin', 'approver']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SupplyRequest $supplyRequest): bool
    {
        if ($user->role === 'hr_admin') {
            return true;
        }

        if ($user->role === 'approver') {
            return $supplyRequest->department_id === $user->department_id;
        }

        return $supplyRequest->user_id === $user->id;
    }

    public function viewApprovalHistory(User $user, SupplyRequest $supplyRequest): bool
    {
        if ($user->role !== 'approver') {
            return false;
        }

        return $supplyRequest->timelines()
            ->where('performed_by', $user->id)
            ->whereIn('action', ['approved', 'rejected'])
            ->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === 'requestor';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SupplyRequest $supplyRequest): bool
    {
        if ($user->role === 'hr_admin') {
            return true;
        }

        if ($user->role === 'approver') {
            return $supplyRequest->department_id === $user->department_id && $supplyRequest->status === SupplyRequest::STATUS_PENDING_APPROVAL;
        }

        return $supplyRequest->user_id === $user->id && $supplyRequest->status === SupplyRequest::STATUS_DRAFT;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SupplyRequest $supplyRequest): bool
    {
        return $supplyRequest->user_id === $user->id && $supplyRequest->status === SupplyRequest::STATUS_DRAFT;
    }
}
