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
        $department = Department::where('code', 'HHR')->first()
            ?? Department::updateOrCreate(
                ['code' => 'ADM'],
                [
                    'name' => 'Administrator',
                    'type' => 'head_office',
                    'area' => 'HO',
                    'cost_center' => '80018',
                    'cost_center_source' => 'manual',
                    'is_custom' => true,
                ]
            );

        User::updateOrCreate(
            ['email' => 'hr_admin@guess.com.ph'],
            [
                'name' => 'Maria Lie Grace Azura',
                'email' => 'hr_admin@guess.com.ph',
                'role' => 'hr_admin',
                'department_id' => $department->id,
                'external_department_reference_id' => $department->external_department_reference_id,
                'cost_center' => $department->cost_center ?? '80018',
                'is_active' => true,
                'password' => Hash::make('Admin123'),
                'email_verified_at' => now(),
                'created_by' => null,
            ]
        );

        $this->command->info('HR Admin department ready: '.$department->name.' ('.$department->code.')');
        $this->command->info('HR Admin created: hr_admin@guess.com.ph / Admin123');
    }
}
