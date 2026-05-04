<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Department>
 */
class DepartmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            // Example: "Tech Solutions Department"
            'name' => $this->faker->company() . ' Department',
            
            // Example: "DEP"
            'code' => strtoupper($this->faker->unique()->lexify('???')),
            
            // Randomly picks between head_office or store
            'type' => $this->faker->randomElement(['head_office', 'store']),
        ];
    }
}