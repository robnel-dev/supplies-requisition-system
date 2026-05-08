<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
            $this->call([
                ExternalDepartmentReferenceSeeder::class,
                DepartmentSeeder::class,
                AdminUserSeeder::class,
            ]);

            //$this->call(SupplySeeder::class);
            // Department::factory(10)->create();
            // User::factory(30)->create();
    }
}
