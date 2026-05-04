<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        //  Create a default department FIRST so the user has a valid department_id
        $department = Department::firstOrCreate(
            ['code' => 'ADM'], 
            [
                'name' => 'Administrator',
                'type' => 'head_office',
            ]
        );

        // 2. Create or update the admin user
        User::updateOrCreate(
            ['email' => 'hr_admin@guess.com.ph'], // Search by email
            [
                'name' => 'Maria Lie Grace Azura',
                'email' => 'hr_admin@guess.com.ph',
                'role' => 'hr_admin', 
                'department_id' => $department->id, // Dynamically assign the ID from the created department
                'cost_center' => '80018',
                'is_active' => true,
                'password' => Hash::make('Admin123'),
                'email_verified_at' => now(),
                'created_by' => null, 
            ]
        );

        $this->command->info('HR Department created: Human Resources (HRD)');
        $this->command->info('HR Admin created: hr_admin@guess.com.ph / Admin123');
    }
}