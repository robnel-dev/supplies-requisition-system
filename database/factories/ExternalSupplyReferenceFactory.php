<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ExternalSupplyReferenceFactory extends Factory
{
    public function definition(): array
    {
        $stock = $this->faker->numberBetween(10, 500);
        $allocated = $this->faker->numberBetween(0, $stock / 2); // Less than total stock
        
        return [
            'company_no' => '888',
            'warehouse_location' => '41D',
            'item_code' => 'SU-' . $this->faker->unique()->bothify('???###-#####'), // Simulating DB2 format
            'item_description' => $this->faker->words(4, true),
            'unit_of_measure' => $this->faker->randomElement(['PCE', 'BOX', 'BOT', 'SET']),
            'stock_quantity' => $stock,
            'allocated_quantity' => $allocated,
            'allocatable_quantity' => $stock - $allocated,
            'MBORTY' => 'N04',
            'MBSTAT' => '20',
        ];
    }
}