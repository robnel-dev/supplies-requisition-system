<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            // Default password for all seeded users 
            'password' => static::$password ??= Hash::make('it@guess'),
            'remember_token' => Str::random(10),
            
            // Custom System Fields
            'role' => fake()->randomElement(['requestor', 'approver']),
            // Assigns a random department ID from the ones generated
            'department_id' => Department::inRandomOrder()->first()->id ?? Department::factory(),
            'cost_center' => fake()->numberBetween(10000, 99999),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}