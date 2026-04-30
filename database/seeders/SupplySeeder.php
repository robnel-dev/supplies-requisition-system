<?php

namespace Database\Seeders;

use App\Models\ExternalSupplyReference;
use App\Models\Supply;
use Illuminate\Database\Seeder;

class SupplySeeder extends Seeder
{
    public function run(): void
    {
        // 1. ACTUAL DATA FROM CSV (Inserted into the .236 External Server)
        $actualItems = [
            ['company_no' => '888', 'warehouse_location' => '41D', 'item_code' => 'SU-CCD004-00001', 'stock_quantity' => 49, 'allocated_quantity' => 0, 'allocatable_quantity' => 49, 'item_description' => 'Dvd Rewritable (Verbatim)', 'unit_of_measure' => 'PCE', 'MBORTY' => 'N04', 'MBSTAT' => '20'],
            ['company_no' => '888', 'warehouse_location' => '41D', 'item_code' => 'SU-CNK001-00006', 'stock_quantity' => 15, 'allocated_quantity' => 0, 'allocatable_quantity' => 15, 'item_description' => 'CMOS Battery', 'unit_of_measure' => 'PCE', 'MBORTY' => 'N04', 'MBSTAT' => '20'],
            ['company_no' => '888', 'warehouse_location' => '41D', 'item_code' => 'SU-CNK003-00037', 'stock_quantity' => 10, 'allocated_quantity' => 0, 'allocatable_quantity' => 10, 'item_description' => 'HP 678 Black', 'unit_of_measure' => 'BOX', 'MBORTY' => 'N04', 'MBSTAT' => '20'],
            ['company_no' => '888', 'warehouse_location' => '41D', 'item_code' => 'SU-CNK003-00038', 'stock_quantity' => 13, 'allocated_quantity' => 0, 'allocatable_quantity' => 13, 'item_description' => 'HP 678 Colored', 'unit_of_measure' => 'BOX', 'MBORTY' => 'N04', 'MBSTAT' => '20'],
            ['company_no' => '888', 'warehouse_location' => '41D', 'item_code' => 'SU-HOS055-00002', 'stock_quantity' => 3,  'allocated_quantity' => 0, 'allocatable_quantity' => 3,  'item_description' => 'Glue Stick - Big', 'unit_of_measure' => 'PCE', 'MBORTY' => 'N04', 'MBSTAT' => '20'],
            ['company_no' => '888', 'warehouse_location' => '41D', 'item_code' => 'SU-HOS057-00002', 'stock_quantity' => 2,  'allocated_quantity' => 0, 'allocatable_quantity' => 2,  'item_description' => 'Paste 1000g (REDSTONE)', 'unit_of_measure' => 'BOT', 'MBORTY' => 'N04', 'MBSTAT' => '20'],
            ['company_no' => '888', 'warehouse_location' => '41D', 'item_code' => 'SU-HOS058-00004', 'stock_quantity' => 21, 'allocated_quantity' => 0, 'allocatable_quantity' => 21, 'item_description' => 'Rubber Band - Big 350g (ARROW)', 'unit_of_measure' => 'BOX', 'MBORTY' => 'N04', 'MBSTAT' => '20'],
        ];

        // Insert or update external DB2 simulator table
        foreach ($actualItems as $item) {
            ExternalSupplyReference::updateOrCreate(
                ['item_code' => $item['item_code']], 
                $item
            );
        }

        // Generate 15 more random fake external items just to pad the table out for testing pagination
        ExternalSupplyReference::factory()->count(15)->create();

        // 2. REGISTER A FEW ITEMS INTO OUR LOCAL HR SYSTEM (Local DB)
        // Let's explicitly register the first 4 real items so we can see them in our Vue table right away
        $itemsToSync = array_slice($actualItems, 0, 4);

        foreach ($itemsToSync as $item) {
            Supply::updateOrCreate(
                ['item_code' => $item['item_code']],
                [
                    // We don't save the item_description here; we let the model fetch it from the external reference!
                    'category' => str_contains($item['item_description'], 'HP') || str_contains($item['item_description'], 'CMOS') ? 'Computer Supplies' : 'Office & Store Supplies',
                    'unit' => strtolower($item['unit_of_measure']),
                    'is_active' => true,
                ]
            );
        }

        // 3. CREATE A CUSTOM HR SUPPLY (Item that does NOT exist in the external table)
        Supply::updateOrCreate(
            ['item_code' => 'CUST-CHAIR-01'],
            [
                'item_description' => 'Special Ergonomic Office Chair (HR Requested)',
                'category' => 'Office & Store Supplies',
                'unit' => 'pcs',
                'is_active' => true,
            ]
        );
    }
}