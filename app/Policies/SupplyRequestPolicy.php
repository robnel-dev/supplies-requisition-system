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

    public function viewReleases(User $user): bool
    {
        return $user->role === 'hr_admin';
    }

    public function viewRelease(User $user, SupplyRequest $supplyRequest): bool
    {
        return $user->role === 'hr_admin'
            && in_array($supplyRequest->status, [
                SupplyRequest::STATUS_APPROVED,
                SupplyRequest::STATUS_RELEASED,
            ], true);
    }

    public function updateReleaseDetails(User $user, SupplyRequest $supplyRequest): bool
    {
        return $user->role === 'hr_admin'
            && $supplyRequest->status === SupplyRequest::STATUS_APPROVED;
    }

    public function releaseRequest(User $user, SupplyRequest $supplyRequest): bool
    {
        return $this->updateReleaseDetails($user, $supplyRequest);
    }

    public function rejectRelease(User $user, SupplyRequest $supplyRequest): bool
    {
        return $this->updateReleaseDetails($user, $supplyRequest);
    }

    public function archiveRelease(User $user, SupplyRequest $supplyRequest): bool
    {
        return $user->role === 'hr_admin'
            && $supplyRequest->status === SupplyRequest::STATUS_RELEASED;
    }

    public function viewArchivedRequests(User $user): bool
    {
        return $user->role === 'hr_admin';
    }

    public function viewArchivedRequest(User $user, SupplyRequest $supplyRequest): bool
    {
        return $user->role === 'hr_admin'
            && $supplyRequest->status === SupplyRequest::STATUS_ARCHIVED
            && $supplyRequest->archived_by === $user->id;
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
            return ! in_array($supplyRequest->status, [
                SupplyRequest::STATUS_RELEASED,
                SupplyRequest::STATUS_ARCHIVED,
            ], true);
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
