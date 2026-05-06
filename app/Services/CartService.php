<?php

namespace App\Services;

use App\Models\SupplyRequest;
use App\Models\SupplyRequestItem;
use App\Models\Supply;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CartService
{
    // Fetches the active draft or creates one if it doesn't exist
    public function getOrCreateDraft($user)
    {
        return SupplyRequest::with('items')->firstOrCreate(
            [
                'user_id' => $user->id,
                'status' => 'draft'
            ],
            [
                'department_id' => $user->department_id,
            ]
        );
    }

    // Adds or updates an item in the draft
    public function addItem($user, $supplyId, $quantity)
    {
        $draft = $this->getOrCreateDraft($user);
        $supply = Supply::findOrFail($supplyId);

        $item = SupplyRequestItem::where('supply_request_id', $draft->id)
            ->where('supply_id', $supply->id)
            ->first();

        if ($item) {
            $item->increment('quantity', $quantity);
        } else {
            $draft->items()->create([
                'supply_id' => $supply->id,
                'item_code' => $supply->item_code,
                'item_description' => $supply->item_description,
                'item_unit' => $supply->unit,
                'quantity' => $quantity,
            ]);
        }
    }

    // Updates quantity directly
    public function updateItemQuantity($user, $itemId, $quantity)
    {
        $draft = $this->getOrCreateDraft($user);
        $draft->items()->where('id', $itemId)->update(['quantity' => $quantity]);
    }

    // Removes an item
    public function removeItem($user, $itemId)
    {
        $draft = $this->getOrCreateDraft($user);
        $draft->items()->where('id', $itemId)->delete();
    }

    // Submits the draft into Pending Approval
    public function checkout($user)
    {
        return DB::transaction(function () use ($user) {
            $draft = $this->getOrCreateDraft($user);

            if ($draft->items()->count() === 0) {
                throw new \Exception("Cannot submit an empty request.");
            }

            // Generate Transaction ID (e.g., COST_CENTER-YYYY-0001)
            $year = now()->format('Y');
            $latest = SupplyRequest::whereYear('request_date', $year)
                ->whereNotNull('request_date')
                ->count() + 1;

            $transactionId = sprintf("%s-%s-%06d", $user->cost_center, $year, $latest);

            // Lock original quantities and change status
            foreach ($draft->items as $item) {
                $item->update(['original_quantity' => $item->quantity]);
            }

            $draft->update([
                'status' => 'pending_approval',
                'transaction_id' => $transactionId,
                'request_date' => now()
            ]);

            return $transactionId;
        });
    }
}
