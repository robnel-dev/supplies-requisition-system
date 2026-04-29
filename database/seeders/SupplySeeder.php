<?php

namespace Database\Seeders;

use App\Models\ExternalSupplyReference;
use App\Models\Supply;
use Illuminate\Database\Seeder;

class SupplySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create 30 items in the "External" database (Not all will be activated in our system)
        $externalItems = ExternalSupplyReference::factory()->count(30)->create();

        // 2. Take 20 of those external items and add them to our internal Supplies registry
        $itemsToRegister = $externalItems->random(20);

        foreach ($itemsToRegister as $item) {
            Supply::factory()->create([
                'item_code' => $item->item_code,
            ]);
        }

        // 3. Create a couple of "Custom" supplies (Items that don't exist externally)
        Supply::create([
            'item_code' => 'CUST-001',
            'item_name' => 'Special Ergonomic Chair',
            'item_description' => 'Requested by HR Director',
            'category' => 'Office & Store Supplies',
            'unit' => 'pcs',
            'is_active' => true,
        ]);
    }
}