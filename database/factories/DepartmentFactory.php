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
            'name' => $this->faker->company() . ' Department',
            'code' => strtoupper($this->faker->unique()->lexify('???')),
            'type' => $this->faker->randomElement(['head_office', 'store']),
            'area' => $this->faker->optional()->randomElement(['HO', 'Area1', 'Area2', 'Area3', 'Area4', 'Area5', 'Area6']),
            'cost_center' => (string) $this->faker->numberBetween(10000, 99999),
            'cost_center_source' => 'manual',
            'is_custom' => true,
        ];
    }
}
