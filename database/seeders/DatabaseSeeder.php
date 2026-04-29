<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
            $this->call(AdminUserSeeder::class);

            Department::factory(10)->create();
            User::factory(30)->create();
    }
}
