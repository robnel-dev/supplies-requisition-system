<?php

namespace App\Services;

use App\Models\SupplyRequest;
use App\Models\SupplyRequestItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReleaseService
{
    private const BUDGETED = 'budgeted';

    private const UNBUDGETED = 'unbudgeted';

    public function updateDetails(SupplyRequest $supplyRequest, User $hrAdmin, array $data): void
    {
        DB::transaction(function () use ($supplyRequest, $hrAdmin, $data) {
            $lockedRequest = $this->lockRequest($supplyRequest);

            $this->ensureHrAdmin($hrAdmin);
            $this->ensureManageable($lockedRequest);

            $changed = $this->syncReleaseDetails($lockedRequest, $data);

            if ($changed) {
                $lockedRequest->timelines()->create([
                    'action' => 'release_details_updated',
                    'description' => 'Release details were updated by HR Admin.',
                    'performed_by' => $hrAdmin->id,
                ]);
            }
        });
    }

    public function release(SupplyRequest $supplyRequest, User $hrAdmin): void
    {
        DB::transaction(function () use ($supplyRequest, $hrAdmin) {
            $lockedRequest = $this->lockRequest($supplyRequest);

            $this->ensureHrAdmin($hrAdmin);
            $this->ensureApproved($lockedRequest);
            $this->ensureReferenceNumbers($lockedRequest);
            $this->ensureItemsReadyForRelease($lockedRequest);

            $lockedRequest->update([
                'status' => SupplyRequest::STATUS_RELEASED,
                'hr_admin_released_by' => $hrAdmin->id,
                'hr_admin_released_at' => now(),
                'hr_admin_notes' => null,
                'rejection_reason' => null,
            ]);

            $lockedRequest->timelines()->create([
                'action' => 'released',
                'description' => 'Request released by HR Admin.',
                'performed_by' => $hrAdmin->id,
            ]);
        });
    }

    public function reject(SupplyRequest $supplyRequest, User $hrAdmin, string $reason): void
    {
        DB::transaction(function () use ($supplyRequest, $hrAdmin, $reason) {
            $lockedRequest = $this->lockRequest($supplyRequest);
            $reason = trim($reason);

            $this->ensureHrAdmin($hrAdmin);
            $this->ensureApproved($lockedRequest);

            $lockedRequest->update([
                'status' => SupplyRequest::STATUS_REJECTED,
                'rejection_reason' => $reason,
                'hr_admin_notes' => $reason,
                'hr_admin_released_by' => null,
                'hr_admin_released_at' => null,
            ]);

            $lockedRequest->timelines()->create([
                'action' => 'rejected',
                'description' => "Request rejected by HR Admin. Reason: {$reason}",
                'performed_by' => $hrAdmin->id,
            ]);
        });
    }

    public function archive(SupplyRequest $supplyRequest, User $hrAdmin): void
    {
        DB::transaction(function () use ($supplyRequest, $hrAdmin) {
            $lockedRequest = $this->lockRequest($supplyRequest);

            $this->ensureHrAdmin($hrAdmin);
            $this->ensureReleased($lockedRequest);
            $this->ensureReferenceNumbers($lockedRequest);

            $lockedRequest->update([
                'status' => SupplyRequest::STATUS_ARCHIVED,
            ]);

            $lockedRequest->timelines()->create([
                'action' => 'archived',
                'description' => 'Released request archived by HR Admin.',
                'performed_by' => $hrAdmin->id,
            ]);
        });
    }

    private function syncReleaseDetails(SupplyRequest $supplyRequest, array $data): bool
    {
        $changed = false;

        $supplyRequest->fill([
            'm3_ro_number' => $this->nullableString($data['m3_ro_number'] ?? null),
            'm3_dr_number' => $this->nullableString($data['m3_dr_number'] ?? null),
        ]);

        if ($supplyRequest->isDirty(['m3_ro_number', 'm3_dr_number'])) {
            $supplyRequest->save();
            $changed = true;
        }

        $submittedItems = collect($data['items'] ?? [])->keyBy('id');
        $items = SupplyRequestItem::where('supply_request_id', $supplyRequest->id)
            ->lockForUpdate()
            ->get();

        $this->ensureEveryItemWasSubmitted($items, $submittedItems);

        foreach ($items as $item) {
            $itemData = $submittedItems->get($item->id);
            $updates = $this->releaseItemUpdates($item, $itemData);

            $item->fill($updates);

            if ($item->isDirty(['budget_type', 'allocated_quantity', 'balance'])) {
                $item->save();
                $changed = true;
            }
        }

        return $changed;
    }

    private function releaseItemUpdates(SupplyRequestItem $item, array $itemData): array
    {
        $budgetType = $this->nullableString($itemData['budget_type'] ?? null);
        $allocatedQuantity = $this->nullableInteger($itemData['allocated_quantity'] ?? null);

        if (! in_array($budgetType, [null, self::BUDGETED, self::UNBUDGETED], true)) {
            throw ValidationException::withMessages([
                'items' => 'Invalid budget type selected.',
            ]);
        }

        if ($budgetType === self::UNBUDGETED) {
            return [
                'budget_type' => $budgetType,
                'allocated_quantity' => null,
                'balance' => 0,
            ];
        }

        if ($budgetType === self::BUDGETED) {
            $allocatedQuantity ??= 0;

            if ($allocatedQuantity > (int) $item->quantity) {
                throw ValidationException::withMessages([
                    'items' => 'HRD allocation cannot be greater than the approved quantity.',
                ]);
            }

            return [
                'budget_type' => $budgetType,
                'allocated_quantity' => $allocatedQuantity,
                'balance' => max((int) $item->quantity - $allocatedQuantity, 0),
            ];
        }

        return [
            'budget_type' => null,
            'allocated_quantity' => null,
            'balance' => null,
        ];
    }

    private function ensureEveryItemWasSubmitted(Collection $items, Collection $submittedItems): void
    {
        $missingItem = $items->first(fn (SupplyRequestItem $item) => ! $submittedItems->has($item->id));

        if ($missingItem) {
            throw ValidationException::withMessages([
                'items' => 'Every request item must be included before saving release details.',
            ]);
        }
    }

    private function ensureItemsReadyForRelease(SupplyRequest $supplyRequest): void
    {
        $items = $supplyRequest->items()
            ->lockForUpdate()
            ->get();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Cannot release a request without items.',
            ]);
        }

        foreach ($items as $item) {
            if (! in_array($item->budget_type, [self::BUDGETED, self::UNBUDGETED], true)) {
                throw ValidationException::withMessages([
                    'items' => 'Please assign a budget type for every item before releasing.',
                ]);
            }

            if ($item->budget_type === self::BUDGETED && $item->allocated_quantity === null) {
                throw ValidationException::withMessages([
                    'items' => 'Please enter an HRD allocation for every budgeted item before releasing.',
                ]);
            }

            if ($item->budget_type === self::BUDGETED && $item->allocated_quantity > $item->quantity) {
                throw ValidationException::withMessages([
                    'items' => 'HRD allocation cannot be greater than the approved quantity.',
                ]);
            }
        }
    }

    private function lockRequest(SupplyRequest $supplyRequest): SupplyRequest
    {
        return SupplyRequest::whereKey($supplyRequest->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function ensureHrAdmin(User $user): void
    {
        abort_if($user->role !== 'hr_admin', 403);
    }

    private function ensureManageable(SupplyRequest $supplyRequest): void
    {
        if (! in_array($supplyRequest->status, [
            SupplyRequest::STATUS_APPROVED,
            SupplyRequest::STATUS_RELEASED,
        ], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only approved or released requests can be managed from Releases.',
            ]);
        }
    }

    private function ensureApproved(SupplyRequest $supplyRequest): void
    {
        if ($supplyRequest->status !== SupplyRequest::STATUS_APPROVED) {
            throw ValidationException::withMessages([
                'status' => 'Only approved requests can be released or rejected.',
            ]);
        }
    }

    private function ensureReleased(SupplyRequest $supplyRequest): void
    {
        if ($supplyRequest->status !== SupplyRequest::STATUS_RELEASED) {
            throw ValidationException::withMessages([
                'status' => 'Only released requests can be archived.',
            ]);
        }
    }

    private function ensureReferenceNumbers(SupplyRequest $supplyRequest): void
    {
        if (! $supplyRequest->m3_ro_number || ! $supplyRequest->m3_dr_number) {
            throw ValidationException::withMessages([
                'm3_ro_number' => 'M3 RO Number and M3 DR Number are required before this action.',
            ]);
        }
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
