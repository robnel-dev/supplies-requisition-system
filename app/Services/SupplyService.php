<?php

namespace App\Services;

use App\Models\Supply;
use App\Models\SupplyRequestItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplyService
{
    public function createSupply(array $data): Supply
    {
        return DB::transaction(function () use ($data) {
            return Supply::create([
                'item_code'        => $data['item_code'],
                'item_description' => $data['item_description'] ?? null,
                'category'         => $data['category'],
                'unit'             => $data['unit'],
                'is_active'        => $data['is_active'] ?? true,
            ]);
        });
    }

    public function updateSupply(Supply $supply, array $data): bool
    {

        return $supply->update([
            'item_description' => $data['item_description'] ?? $supply->item_description,
            'category'         => $data['category'],
            'unit'             => $data['unit'],
        ]);
    }

    public function toggleActiveStatus(Supply $supply): bool
    {
        return $supply->update(['is_active' => !$supply->is_active]);
    }

    public function deleteSupply(Supply $supply): void
    {

        $isUsedInRequests = SupplyRequestItem::where('supply_id', $supply->id)->exists();

        if ($isUsedInRequests) {
            throw ValidationException::withMessages([
                'delete' => 'Cannot delete this supply. It has been used in one or more requests.',
            ]);
        }

        $supply->delete();
    }
}
