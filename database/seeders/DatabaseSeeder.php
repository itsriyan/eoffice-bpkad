<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Base user factory example
        // User::factory(10)->create();

        // Seed roles & permissions first
        $this->call(RolesAndPermissionsSeeder::class);
        // Seed grade (golongan) master data
        $this->call(GradesSeeder::class);

        // Seed users & employees for each role
        $this->call(UsersAndEmployeesSeeder::class);
    }
}