<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ExternalSupplyReferenceFactory extends Factory
{
    public function definition(): array
    {
        $available = $this->faker->numberBetween(10, 500);
        
        return [
            'item_code' => 'ITM-' . $this->faker->unique()->numberBetween(1000, 9999),
            'item_name' => $this->faker->words(3, true),
            'item_description' => $this->faker->sentence(),
            'available_stocks' => $available,
            'allocatable_stocks' => $this->faker->numberBetween(0, $available), // Always <= available
        ];
    }
}