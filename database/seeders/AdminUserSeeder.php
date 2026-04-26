<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'hr_admin@guess.com.ph'],
            [
                'name' => 'Maria Lie Grace Azura',
                'email' => 'hr_admin@guess.com.ph',
                'department' => 'Human Resources',
                'cost_center' => '80018',
                'role' => 'hr_admin',
                'is_active' => true,
                'password' => Hash::make('Admin123'),
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('HR Admin created: hr_admin@guess.com.ph / Admin123');
    }
}
