<?php

namespace App\Services;

use App\Models\Supply;
use Illuminate\Support\Facades\DB;

class SupplyService
{
    public function createSupply(array $data): Supply
    {
        return DB::transaction(function () use ($data) {
            return Supply::create([
                'item_code' => $data['item_code'],
                'item_description' => $data['item_description'], 
                'category' => $data['category'],
                'unit' => $data['unit'],
                'is_active' => $data['is_active'] ?? true,
            ]);
        });
    }

    public function updateSupply(Supply $supply, array $data): bool
    {
        return $supply->update([
            'category' => $data['category'],
            'unit' => $data['unit'],
        ]);
    }

    public function toggleActiveStatus(Supply $supply): bool
    {
        return $supply->update(['is_active' => !$supply->is_active]);
    }
}