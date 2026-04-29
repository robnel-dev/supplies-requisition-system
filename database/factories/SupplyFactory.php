<?php

namespace Database\Factories;

use App\Models\ExternalSupplyReference;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplyFactory extends Factory
{
    public function definition(): array
    {
        // By default, create a matching external reference so relationships work
        $external = ExternalSupplyReference::factory()->create();

        return [
            'item_code' => $external->item_code,
            // Leave internal name/desc null so it falls back to the external one
            'item_name' => null, 
            'item_description' => null,
            'category' => $this->faker->randomElement(['Computer Supplies', 'Office & Store Supplies', 'Cleaning']),
            'unit' => $this->faker->randomElement(['pcs', 'ream', 'roll', 'box', 'set', 'pack']),
            'is_active' => $this->faker->boolean(80), // 80% chance to be active
        ];
    }
}