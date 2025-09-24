<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all users
        $users = User::all();

        if ($users->isEmpty()) {
            return; // No users to create transactions for
        }

        $transactions = [];

        // Create sample deposit transactions
        foreach ($users as $user) {
            // Approved deposits
            $transactions[] = [
                'user_id' => $user->id,
                'type' => 'deposit',
                'amount' => rand(1000, 50000),
                'status' => 'approved',
                'transaction_date' => Carbon::now()->subDays(rand(1, 30)),
                'reference' => 'TXN' . date('Ymd') . strtoupper(uniqid()),
                'created_at' => Carbon::now()->subDays(rand(1, 30)),
                'updated_at' => Carbon::now()->subDays(rand(1, 30)),
            ];

            // Pending deposits
            if (rand(1, 3) === 1) { // 33% chance of having pending deposits
                $transactions[] = [
                    'user_id' => $user->id,
                    'type' => 'deposit',
                    'amount' => rand(500, 10000),
                    'status' => 'pending',
                    'transaction_date' => Carbon::now()->subHours(rand(1, 24)),
                    'reference' => 'TXN' . date('Ymd') . strtoupper(uniqid()),
                    'created_at' => Carbon::now()->subHours(rand(1, 24)),
                    'updated_at' => Carbon::now()->subHours(rand(1, 24)),
                ];
            }

            // Rejected deposits
            if (rand(1, 5) === 1) { // 20% chance of having rejected deposits
                $transactions[] = [
                    'user_id' => $user->id,
                    'type' => 'deposit',
                    'amount' => rand(200, 2000),
                    'status' => 'rejected',
                    'transaction_date' => Carbon::now()->subDays(rand(1, 10)),
                    'reference' => 'TXN' . date('Ymd') . strtoupper(uniqid()),
                    'created_at' => Carbon::now()->subDays(rand(1, 10)),
                    'updated_at' => Carbon::now()->subDays(rand(1, 10)),
                ];
            }

            // Approved withdrawals
            if (rand(1, 2) === 1) { // 50% chance of having withdrawals
                $transactions[] = [
                    'user_id' => $user->id,
                    'type' => 'withdrawal',
                    'amount' => rand(500, 10000),
                    'status' => 'approved',
                    'transaction_date' => Carbon::now()->subDays(rand(1, 15)),
                    'reference' => 'TXN' . date('Ymd') . strtoupper(uniqid()),
                    'created_at' => Carbon::now()->subDays(rand(1, 15)),
                    'updated_at' => Carbon::now()->subDays(rand(1, 15)),
                ];
            }

            // Pending withdrawals
            if (rand(1, 4) === 1) { // 25% chance of having pending withdrawals
                $transactions[] = [
                    'user_id' => $user->id,
                    'type' => 'withdrawal',
                    'amount' => rand(300, 5000),
                    'status' => 'pending',
                    'transaction_date' => Carbon::now()->subHours(rand(1, 12)),
                    'reference' => 'TXN' . date('Ymd') . strtoupper(uniqid()),
                    'created_at' => Carbon::now()->subHours(rand(1, 12)),
                    'updated_at' => Carbon::now()->subHours(rand(1, 12)),
                ];
            }
        }

        // Insert all transactions
        foreach (array_chunk($transactions, 100) as $chunk) {
            Transaction::insert($chunk);
        }
    }
}
