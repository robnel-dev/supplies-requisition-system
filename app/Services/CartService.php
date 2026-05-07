<?php

namespace App\Services;

use App\Models\SupplyRequest;
use App\Models\SupplyRequestItem;
use App\Models\Supply;
use Illuminate\Support\Facades\DB;

class CartService
{

    // Fetches the active draft or creates one if it doesn't exist.
    public function getOrCreateDraft($user): SupplyRequest
    {
        $draft = SupplyRequest::with('items.supply')
            ->where('user_id', $user->id)
            ->where('status', 'draft')
            ->first();

        if (! $draft) {
            $draft = SupplyRequest::create([
                'user_id'       => $user->id,
                'department_id' => $user->department_id,
                'status'        => 'draft',
            ]);
        }

        return $draft;
    }

    // Adds or increments an item in the draft.
    public function addItem($user, int $supplyId, int $quantity): void
    {
        $draft  = $this->getOrCreateDraft($user);
        $supply = Supply::where('id', $supplyId)
            ->where('is_active', true)
            ->firstOrFail();

        $item = SupplyRequestItem::where('supply_request_id', $draft->id)
            ->where('supply_id', $supply->id)
            ->first();

        if ($item) {
            $item->increment('quantity', $quantity);
        } else {
            $draft->items()->create([
                'supply_id'        => $supply->id,
                'item_code'        => $supply->item_code,
                'item_description' => $supply->item_description ?? '',
                'item_unit'        => $supply->unit,
                'quantity'         => $quantity,
            ]);
        }
    }

    // Updates the quantity of a cart item (ownership-safe).
    public function updateItemQuantity($user, int $itemId, int $quantity): void
    {
        $draft = $this->getOrCreateDraft($user);
        $draft->items()->where('id', $itemId)->update(['quantity' => $quantity]);
    }

    // Removes an item from the cart (ownership-safe).
    public function removeItem($user, int $itemId): void
    {
        $draft = $this->getOrCreateDraft($user);
        $draft->items()->where('id', $itemId)->delete();
    }

    public function checkout($user): string
    {
        return DB::transaction(function () use ($user) {
            // Lock the draft to prevent duplicate submissions from same user
            $draft = SupplyRequest::where('user_id', $user->id)
                ->where('status', 'draft')
                ->lockForUpdate()
                ->firstOrFail();

            if ($draft->items()->count() === 0) {
                throw new \Exception('Cannot submit an empty request.');
            }

            $year      = now()->format('Y');
            $costCenter = $user->cost_center;

            /**
             * Count only submissions for THIS cost center in THIS year.
             * Lock for update to prevent race conditions between concurrent checkouts.
             *
             * NOTE: join with users to filter by cost_center because the
             * cost_center is on the user, not on supply_requests directly.
             */
            $count = SupplyRequest::join('users', 'supply_requests.user_id', '=', 'users.id')
                ->where('users.cost_center', $costCenter)
                ->whereYear('supply_requests.created_at', $year)
                ->whereNotNull('supply_requests.transaction_id')
                ->lockForUpdate()
                ->count();

            $series        = $count + 1;
            $transactionId = sprintf('%s-%s-%06d', $costCenter, $year, $series);

            // Snapshot the requested quantity at submission time
            $draft->items()->update(['original_quantity' => DB::raw('quantity')]);

            $draft->update([
                'status'         => 'pending_approval',
                'transaction_id' => $transactionId,
                'request_date'   => now(),
            ]);

            // Record the first timeline event
            $draft->timelines()->create([
                'action'      => 'submitted',
                'description' => 'Request submitted and is now pending approval.',
                'performed_by' => $user->id,
            ]);

            return $transactionId;
        });
    }
}
