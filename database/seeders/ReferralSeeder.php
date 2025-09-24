<?php

namespace Database\Seeders;

use App\Models\Referral;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ReferralSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test users for referrals (using updateOrCreate to avoid duplicates)
        $referrer = User::updateOrCreate(
            ['email' => 'referrer@test.com'],
            [
                'name' => 'John Referrer',
                'password' => Hash::make('password'),
                'role' => 'user',
                'status' => 'active',
            ]
        );

        $referred1 = User::updateOrCreate(
            ['email' => 'alice@test.com'],
            [
                'name' => 'Alice Referred',
                'password' => Hash::make('password'),
                'role' => 'user',
                'status' => 'active',
            ]
        );

        $referred2 = User::updateOrCreate(
            ['email' => 'bob@test.com'],
            [
                'name' => 'Bob Referred',
                'password' => Hash::make('password'),
                'role' => 'user',
                'status' => 'active',
            ]
        );

        $referred3 = User::updateOrCreate(
            ['email' => 'charlie@test.com'],
            [
                'name' => 'Charlie Referred',
                'password' => Hash::make('password'),
                'role' => 'user',
                'status' => 'active',
            ]
        );

        $referred4 = User::updateOrCreate(
            ['email' => 'diana@test.com'],
            [
                'name' => 'Diana Referred',
                'password' => Hash::make('password'),
                'role' => 'user',
                'status' => 'active',
            ]
        );

        $referred5 = User::updateOrCreate(
            ['email' => 'eve@test.com'],
            [
                'name' => 'Eve Referred',
                'password' => Hash::make('password'),
                'role' => 'user',
                'status' => 'active',
            ]
        );

        // Create pending referrals (2) - using firstOrCreate to avoid duplicates
        Referral::firstOrCreate(
            ['referrer_id' => $referrer->id, 'referred_id' => $referred1->id],
            [
                'bonus_amount' => 10.00,
                'status' => 'pending',
                'notes' => null,
            ]
        );

        Referral::firstOrCreate(
            ['referrer_id' => $referrer->id, 'referred_id' => $referred2->id],
            [
                'bonus_amount' => 10.00,
                'status' => 'pending',
                'notes' => null,
            ]
        );

        // Create confirmed referrals (2) with bonus transactions
        $confirmedReferral1 = Referral::firstOrCreate(
            ['referrer_id' => $referrer->id, 'referred_id' => $referred3->id],
            [
                'bonus_amount' => 10.00,
                'status' => 'confirmed',
                'notes' => 'Approved by admin',
            ]
        );

        $confirmedReferral2 = Referral::firstOrCreate(
            ['referrer_id' => $referrer->id, 'referred_id' => $referred4->id],
            [
                'bonus_amount' => 15.00,
                'status' => 'confirmed',
                'notes' => 'Special bonus approved',
            ]
        );

        // Create bonus transactions for confirmed referrals (only if they don't exist)
        Transaction::firstOrCreate(
            ['user_id' => $referrer->id, 'type' => 'referral_bonus', 'amount' => 10.00, 'reference' => 'TXN' . date('Ymd') . 'REF001'],
            [
                'status' => 'completed',
                'transaction_date' => now(),
            ]
        );

        Transaction::firstOrCreate(
            ['user_id' => $referrer->id, 'type' => 'referral_bonus', 'amount' => 15.00, 'reference' => 'TXN' . date('Ymd') . 'REF002'],
            [
                'status' => 'completed',
                'transaction_date' => now(),
            ]
        );

        // Create cancelled referral (1)
        Referral::firstOrCreate(
            ['referrer_id' => $referrer->id, 'referred_id' => $referred5->id],
            [
                'bonus_amount' => 10.00,
                'status' => 'cancelled',
                'notes' => 'Rejected due to suspicious activity',
            ]
        );
    }
}
