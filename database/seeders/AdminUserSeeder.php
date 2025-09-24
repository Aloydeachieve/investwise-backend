<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@investwise.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        // Create test user
        User::create([
            'name' => 'Test User',
            'email' => 'user@investwise.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'status' => 'active',
        ]);
    }
}
