<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Payout;
use App\Models\User;

class PayoutSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some existing users or create test users
        $users = User::take(4)->get();

        if ($users->count() < 4) {
            // Create additional test users if needed
            for ($i = $users->count(); $i < 4; $i++) {
                $user = User::create([
                    'name' => 'Test User ' . ($i + 1),
                    'email' => 'testuser' . ($i + 1) . '@example.com',
                    'password' => bcrypt('password'),
                    'role' => 'user',
                    'status' => 'active',
                ]);
                $users->push($user);
            }
        }

        // Create 2 pending payouts
        Payout::create([
            'user_id' => $users[0]->id,
            'amount' => 100.00,
            'status' => 'pending',
            'method' => 'bank transfer',
            'notes' => null,
        ]);

        Payout::create([
            'user_id' => $users[1]->id,
            'amount' => 250.50,
            'status' => 'pending',
            'method' => 'crypto wallet',
            'notes' => null,
        ]);

        // Create 1 approved payout with transaction reference
        Payout::create([
            'user_id' => $users[2]->id,
            'amount' => 500.00,
            'status' => 'approved',
            'method' => 'bank transfer',
            'transaction_reference' => 'TXN2025001',
            'notes' => 'Approved by admin',
        ]);

        // Create 1 rejected payout
        Payout::create([
            'user_id' => $users[3]->id,
            'amount' => 75.25,
            'status' => 'rejected',
            'method' => 'crypto wallet',
            'transaction_reference' => null,
            'notes' => 'Insufficient documentation provided',
        ]);
    }
}
