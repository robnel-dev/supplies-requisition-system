<?php

namespace App\Services;

use App\Models\SupplyRequest;
use App\Models\SupplyRequestItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApprovalService
{
    public function approve(SupplyRequest $supplyRequest, User $approver): void
    {
        DB::transaction(function () use ($supplyRequest, $approver) {
            $lockedRequest = $this->lockRequest($supplyRequest);

            $this->ensureApproverDepartment($lockedRequest, $approver);
            $this->ensurePending($lockedRequest);
            $this->ensureRequestHasValidItems($lockedRequest);

            $lockedRequest->update([
                'status' => SupplyRequest::STATUS_APPROVED,
                'approver_id' => $approver->id,
                'manager_approved_by' => $approver->id,
                'manager_approved_at' => now(),
                'rejection_reason' => null,
            ]);

            $lockedRequest->timelines()->create([
                'action' => 'approved',
                'description' => 'Request approved by department approver.',
                'performed_by' => $approver->id,
            ]);
        });
    }

    public function reject(SupplyRequest $supplyRequest, User $approver, string $reason): void
    {
        DB::transaction(function () use ($supplyRequest, $approver, $reason) {
            $lockedRequest = $this->lockRequest($supplyRequest);
            $reason = trim($reason);

            $this->ensureApproverDepartment($lockedRequest, $approver);
            $this->ensurePending($lockedRequest);

            $lockedRequest->update([
                'status' => SupplyRequest::STATUS_REJECTED,
                'approver_id' => $approver->id,
                'rejection_reason' => $reason,
            ]);

            $lockedRequest->timelines()->create([
                'action' => 'rejected',
                'description' => "Request rejected by department approver. Reason: {$reason}",
                'performed_by' => $approver->id,
            ]);
        });
    }

    public function updateItemQuantity(
        SupplyRequest $supplyRequest,
        SupplyRequestItem $item,
        User $approver,
        int $quantity
    ): void {
        DB::transaction(function () use ($supplyRequest, $item, $approver, $quantity) {
            $lockedRequest = $this->lockRequest($supplyRequest);
            $lockedItem = $this->lockItemForRequest($lockedRequest, $item);

            $this->ensureApproverDepartment($lockedRequest, $approver);
            $this->ensurePending($lockedRequest);

            $oldQuantity = (int) $lockedItem->quantity;

            if ($oldQuantity === $quantity) {
                return;
            }

            $lockedItem->update([
                'quantity' => $quantity,
            ]);

            $lockedRequest->timelines()->create([
                'action' => 'item_updated',
                'description' => sprintf(
                    'Quantity for %s changed from %d to %d by department approver.',
                    $this->itemLabel($lockedItem),
                    $oldQuantity,
                    $quantity
                ),
                'performed_by' => $approver->id,
            ]);
        });
    }

    public function removeItem(SupplyRequest $supplyRequest, SupplyRequestItem $item, User $approver): void
    {
        DB::transaction(function () use ($supplyRequest, $item, $approver) {
            $lockedRequest = $this->lockRequest($supplyRequest);
            $lockedItem = $this->lockItemForRequest($lockedRequest, $item);

            $this->ensureApproverDepartment($lockedRequest, $approver);
            $this->ensurePending($lockedRequest);

            if ($lockedRequest->items()->count() <= 1) {
                throw ValidationException::withMessages([
                    'items' => 'A request must contain at least one item.',
                ]);
            }

            $itemLabel = $this->itemLabel($lockedItem);
            $lockedItem->delete();

            $lockedRequest->timelines()->create([
                'action' => 'item_removed',
                'description' => "{$itemLabel} was removed from the request by department approver.",
                'performed_by' => $approver->id,
            ]);
        });
    }

    private function lockRequest(SupplyRequest $supplyRequest): SupplyRequest
    {
        return SupplyRequest::whereKey($supplyRequest->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function lockItemForRequest(SupplyRequest $supplyRequest, SupplyRequestItem $item): SupplyRequestItem
    {
        return SupplyRequestItem::whereKey($item->id)
            ->where('supply_request_id', $supplyRequest->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function ensureApproverDepartment(SupplyRequest $supplyRequest, User $approver): void
    {
        abort_if(
            ! $approver->department_id || $supplyRequest->department_id !== $approver->department_id,
            403
        );
    }

    private function ensurePending(SupplyRequest $supplyRequest): void
    {
        if ($supplyRequest->status !== SupplyRequest::STATUS_PENDING_APPROVAL) {
            throw ValidationException::withMessages([
                'status' => 'Only pending requests can be modified by an approver.',
            ]);
        }
    }

    private function ensureRequestHasValidItems(SupplyRequest $supplyRequest): void
    {
        if (! $supplyRequest->items()->exists()) {
            throw ValidationException::withMessages([
                'items' => 'Cannot approve a request without items.',
            ]);
        }

        if ($supplyRequest->items()->where('quantity', '<', 1)->exists()) {
            throw ValidationException::withMessages([
                'items' => 'Cannot approve a request with invalid item quantities.',
            ]);
        }
    }

    private function itemLabel(SupplyRequestItem $item): string
    {
        $label = $item->item_code ?: 'Item';

        if ($item->item_description) {
            $label .= ' - ' . Str::limit($item->item_description, 60);
        }

        return $label;
    }
}
