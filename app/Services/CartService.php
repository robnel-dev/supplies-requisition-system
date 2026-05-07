<?php

namespace App\Services;

use App\Models\SupplyRequest;
use App\Models\SupplyRequestItem;
use App\Models\Supply;
use Illuminate\Support\Facades\DB;

class CartService
{

    // fetches the active draft or creates one if it doesn't exist.
    public function getOrCreateDraft($user): SupplyRequest
    {
        return SupplyRequest::with('items.supply')->firstOrCreate(
            [
                'user_id' => $user->id,
                'status'  => 'draft',
            ],
            [
                'department_id' => $user->department_id,
            ]
        );
    }


    // Adds or updates an item in the draft.

    public function addItem($user, int $supplyId, int $quantity): void
    {
        $draft  = $this->getOrCreateDraft($user);
        $supply = Supply::where('id', $supplyId)->where('is_active', true)->firstOrFail();

        $item = SupplyRequestItem::where('supply_request_id', $draft->id)
            ->where('supply_id', $supply->id)
            ->first();

        if ($item) {
            // Increment quantity if item already in cart
            $item->increment('quantity', $quantity);
        } else {
            $draft->items()->create([
                'supply_id'        => $supply->id,
                'item_code'        => $supply->item_code,
                'item_description' => $supply->item_description,
                'item_unit'        => $supply->unit,
                'quantity'         => $quantity,
            ]);
        }
    }


    // Updates the quantity of a specific cart item.
    public function updateItemQuantity($user, int $itemId, int $quantity): void
    {
        $draft = $this->getOrCreateDraft($user);

        // Only update items that belong to THIS user's draft
        $draft->items()->where('id', $itemId)->update(['quantity' => $quantity]);
    }


    // Removes an item from the cart.
    public function removeItem($user, int $itemId): void
    {
        $draft = $this->getOrCreateDraft($user);
        $draft->items()->where('id', $itemId)->delete();
    }

    // Submits the draft for approval.
    public function checkout($user): string
    {
        return DB::transaction(function () use ($user) {
            // Lock the draft row to prevent duplicate submissions
            $draft = SupplyRequest::where('user_id', $user->id)
                ->where('status', 'draft')
                ->lockForUpdate()
                ->firstOrFail();

            if ($draft->items()->count() === 0) {
                throw new \Exception('Cannot submit an empty request.');
            }

            // Generate Transaction ID atomically
            $year   = now()->format('Y');
            $count  = SupplyRequest::whereYear('created_at', $year)
                ->whereNotNull('transaction_id')
                ->lockForUpdate()
                ->count();

            $series        = $count + 1;
            $transactionId = sprintf('%s-%s-%06d', $user->cost_center, $year, $series);

            // Lock original quantities at submission time
            $draft->items()->update(['original_quantity' => DB::raw('quantity')]);

            $draft->update([
                'status'         => 'pending_approval',
                'transaction_id' => $transactionId,
                'request_date'   => now(),
            ]);

            return $transactionId;
        });
    }
}
