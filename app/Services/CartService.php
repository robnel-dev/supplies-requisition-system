<?php

namespace App\Services;

use App\Models\Supply;
use App\Models\SupplyRequest;
use App\Models\SupplyRequestItem;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function getActiveDraft($user): ?SupplyRequest
    {
        return SupplyRequest::with('items.supply')
            ->where('user_id', $user->id)
            ->where('status', SupplyRequest::STATUS_DRAFT)
            ->first();
    }

    private function getOrCreateDraft($user): SupplyRequest
    {
        $draft = $this->getActiveDraft($user);

        if (! $draft) {
            $draft = SupplyRequest::create([
                'user_id'       => $user->id,
                'department_id' => $user->department_id,
                'status'        => SupplyRequest::STATUS_DRAFT,
            ]);
        }

        return $draft;
    }

    public function addItem($user, int $supplyId, int $quantity): void
    {
        $draft = $this->getOrCreateDraft($user);
        $supply = Supply::where('id', $supplyId)
            ->where('is_active', true)
            ->firstOrFail();

        $item = SupplyRequestItem::where('supply_request_id', $draft->id)
            ->where('supply_id', $supply->id)
            ->first();

        if ($item) {
            $item->increment('quantity', $quantity);
            return;
        }

        $draft->items()->create([
            // Snapshot item fields so submitted requests stay readable if supply data changes.
            'supply_id'        => $supply->id,
            'item_code'        => $supply->item_code,
            'item_description' => $supply->item_description ?? '',
            'item_unit'        => $supply->unit,
            'quantity'         => $quantity,
        ]);
    }

    public function updateItemQuantity($user, int $itemId, int $quantity): void
    {
        $draft = $this->getActiveDraft($user);
        abort_unless($draft, 404);

        $draft->items()->where('id', $itemId)->update(['quantity' => $quantity]);
    }

    public function removeItem($user, int $itemId): void
    {
        $draft = $this->getActiveDraft($user);
        abort_unless($draft, 404);

        $draft->items()->where('id', $itemId)->delete();

        // Empty drafts are removed so they do not block editing another request.
        if ($draft->items()->count() === 0) {
            $draft->delete();
        }
    }

    public function checkout($user): string
    {
        return DB::transaction(function () use ($user) {
            // Lock the draft row to prevent double-submit race conditions.
            $draft = SupplyRequest::where('user_id', $user->id)
                ->where('status', SupplyRequest::STATUS_DRAFT)
                ->lockForUpdate()
                ->first();

            if (! $draft) {
                throw new \Exception('No active draft found to submit.');
            }

            if ($draft->items()->count() === 0) {
                throw new \Exception('Cannot submit an empty request.');
            }

            $transactionId = $draft->transaction_id ?: $this->generateTransactionId($user);

            // Preserve the submitted quantity before approvers or HR adjust fulfillment fields.
            $draft->items()->update(['original_quantity' => DB::raw('quantity')]);

            $draft->update([
                'status'         => SupplyRequest::STATUS_PENDING_APPROVAL,
                'transaction_id' => $transactionId,
                'request_date'   => now(),
            ]);

            $draft->timelines()->create([
                'action'       => 'submitted',
                'description'  => 'Request submitted and is now pending approval.',
                'performed_by' => $user->id,
            ]);

            return $transactionId;
        });
    }

    private function generateTransactionId($user): string
    {
        $year = now()->format('Y');
        $costCenter = $user->cost_center;

        // Transaction IDs restart per cost center each year.
        $maxSeries = SupplyRequest::where('transaction_id', 'like', "{$costCenter}-{$year}-%")
            ->max(DB::raw("CAST(SUBSTRING_INDEX(transaction_id, '-', -1) AS UNSIGNED)"));

        $series = ($maxSeries ?? 0) + 1;

        return sprintf('%s-%s-%06d', $costCenter, $year, $series);
    }

    public function reopenForEdit($user, SupplyRequest $supplyRequest): void
    {
        DB::transaction(function () use ($user, $supplyRequest) {
            // Lock the original request so two edit actions cannot reopen it differently.
            $locked = SupplyRequest::where('id', $supplyRequest->id)
                ->lockForUpdate()
                ->first();

            if ($locked->status === SupplyRequest::STATUS_DRAFT) {
                return;
            }

            if ($locked->status !== SupplyRequest::STATUS_PENDING_APPROVAL) {
                throw new \Exception('Only requests pending approval can be edited.');
            }

            // The catalog drawer assumes one active draft per requestor.
            $existingDraft = SupplyRequest::where('user_id', $user->id)
                ->where('status', SupplyRequest::STATUS_DRAFT)
                ->where('id', '!=', $supplyRequest->id)
                ->lockForUpdate()
                ->first();

            if ($existingDraft) {
                throw new \Exception(
                    'You have another unsubmitted request in progress. ' .
                        'Please submit or clear your current draft before editing this request.'
                );
            }

            $locked->update([
                'status' => SupplyRequest::STATUS_DRAFT,
            ]);

            $locked->timelines()->create([
                'action'       => 'reopened',
                'description'  => 'Request reopened for editing by the requestor.',
                'performed_by' => $user->id,
            ]);
        });
    }
}
