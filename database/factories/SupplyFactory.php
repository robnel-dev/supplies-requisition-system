<?php

namespace Database\Factories;

use App\Models\ExternalSupplyReference;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplyFactory extends Factory
{
    public function definition(): array
    {
        // Generate an external reference to link to
        $external = ExternalSupplyReference::factory()->create();

        return [
            'item_code' => $external->item_code, // Map to DB2 item_code
            'item_description' => null, // Leave null so it falls back to the external DB2 description
            'category' => $this->faker->randomElement(['Computer Supplies', 'Office & Store Supplies', 'Cleaning']),
            'unit' => $external->unit_of_measure, // Auto-sync the unit
            'is_active' => $this->faker->boolean(85), // 85% chance of being active
        ];
    }
}