<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Transaction;
use App\Models\Investment;
use App\Models\Payout;
use App\Models\Referral;
use App\Models\KycSubmission;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminDashboardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    
    public function run(): void
    {
        // Create admin user if not exists
        $admin = User::firstOrCreate(
            ['email' => 'admin@investwise.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // Create system settings
        $this->createSystemSettings();

        // Create test users
        $users = $this->createTestUsers();

        // Create transactions (deposits, withdrawals, referral bonuses)
        $this->createTransactions($users);

        // Create investments
        $this->createInvestments($users);

        // Create payouts
        $this->createPayouts($users);

        // Create referrals
        $this->createReferrals($users);

        // Create KYC submissions
        $this->createKycSubmissions($users);

        // Create audit logs
        $this->createAuditLogs($admin, $users);

        $this->command->info('Admin Dashboard test data created successfully!');
    }

    private function createSystemSettings()
    {
        SystemSetting::set('min_deposit_amount', 100, 'number', 'Minimum deposit amount allowed', true);
        SystemSetting::set('max_withdrawal_amount', 10000, 'number', 'Maximum withdrawal amount allowed', true);
        SystemSetting::set('referral_bonus_rate', 5, 'number', 'Referral bonus percentage', true);
        SystemSetting::set('platform_fee_rate', 2, 'number', 'Platform fee percentage', true);
        SystemSetting::set('kyc_required', true, 'boolean', 'Whether KYC is required for transactions', true);
    }

    private function createTestUsers()
    {
        $users = [];

        // Create 15 test users
        for ($i = 1; $i <= 15; $i++) {
            $user = User::create([
                'name' => "Test User {$i}",
                'email' => "user{$i}@example.com",
                'password' => Hash::make('password123'),
                'role' => 'user',
                'status' => $i <= 12 ? 'active' : 'suspended', // 12 active, 3 suspended
                'email_verified_at' => now()->subDays(rand(1, 30)),
            ]);
            $users[] = $user;
        }

        return $users;
    }

    private function createTransactions($users)
    {
        $transactionTypes = ['deposit', 'withdrawal', 'referral_bonus'];
        $statuses = ['pending', 'approved', 'rejected'];

        foreach ($users as $user) {
            // Create 3-8 transactions per user
            $transactionCount = rand(3, 8);

            for ($i = 0; $i < $transactionCount; $i++) {
                $type = $transactionTypes[array_rand($transactionTypes)];
                $status = $statuses[array_rand($statuses)];
                $amount = match ($type) {
                    'deposit' => rand(100, 5000),
                    'withdrawal' => rand(50, 2000),
                    'referral_bonus' => rand(10, 100),
                };

                Transaction::create([
                    'user_id' => $user->id,
                    'type' => $type,
                    'amount' => $amount,
                    'status' => $status,
                    'transaction_date' => now()->subDays(rand(0, 30)),
                    'reference' => 'TXN' . date('Ymd') . strtoupper(uniqid()),
                ]);
            }
        }
    }

    private function createInvestments($users)
    {
        $investmentStatuses = ['active', 'matured', 'closed'];

        foreach ($users as $user) {
            // Create 1-4 investments per user
            $investmentCount = rand(1, 4);

            for ($i = 0; $i < $investmentCount; $i++) {
                $status = $investmentStatuses[array_rand($investmentStatuses)];
                $amount = rand(500, 10000);

                Investment::create([
                    'user_id' => $user->id,
                    'plan_id' => rand(1, 5), // Assuming 5 plans exist
                    'amount' => $amount,
                    'status' => $status,
                    'start_date' => now()->subDays(rand(0, 60)),
                    'end_date' => $status === 'active' ? now()->addDays(rand(30, 365)) : now()->subDays(rand(0, 30)),
                ]);
            }
        }
    }

    private function createPayouts($users)
    {
        $payoutStatuses = ['pending', 'approved', 'rejected'];

        foreach ($users as $user) {
            // Create 1-3 payouts per user
            $payoutCount = rand(1, 3);

            for ($i = 0; $i < $payoutCount; $i++) {
                $status = $payoutStatuses[array_rand($payoutStatuses)];
                $amount = rand(100, 3000);

                Payout::create([
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'status' => $status,
                    'payment_method' => 'bank_transfer',
                    'account_details' => json_encode([
                        'bank_name' => 'Test Bank',
                        'account_number' => '1234567890',
                        'account_name' => $user->name,
                    ]),
                    'notes' => $status === 'rejected' ? 'Insufficient funds' : null,
                    'transaction_reference' => $status === 'approved' ? 'PAY' . strtoupper(uniqid()) : null,
                ]);
            }
        }
    }

    private function createReferrals($users)
    {
        // Create referral relationships
        for ($i = 0; $i < count($users) - 1; $i++) {
            // Each user refers 1-3 other users
            $referralCount = rand(1, 3);
            $referrer = $users[$i];

            for ($j = 0; $j < $referralCount; $j++) {
                $referredIndex = rand($i + 1, count($users) - 1);
                $referred = $users[$referredIndex];

                // Only create if referral doesn't already exist
                $existingReferral = Referral::where('referrer_id', $referrer->id)
                    ->where('referred_id', $referred->id)
                    ->first();

                if (!$existingReferral) {
                    $bonusAmount = rand(50, 200);

                    Referral::create([
                        'referrer_id' => $referrer->id,
                        'referred_id' => $referred->id,
                        'bonus_amount' => $bonusAmount,
                        'status' => rand(1, 10) <= 7 ? 'confirmed' : 'pending', // 70% confirmed
                        'notes' => rand(1, 10) <= 2 ? 'Referral bonus credited' : null,
                    ]);
                }
            }
        }
    }

    private function createKycSubmissions($users)
    {
        $kycStatuses = ['pending', 'approved', 'rejected'];

        foreach ($users as $user) {
            // 60% of users have KYC submissions
            if (rand(1, 10) <= 6) {
                $status = $kycStatuses[array_rand($kycStatuses)];

                KycSubmission::create([
                    'user_id' => $user->id,
                    'document_type' => 'passport',
                    'document_number' => 'P' . strtoupper(uniqid()),
                    'status' => $status,
                    'document_path' => 'kyc_documents/passport_' . $user->id . '.pdf',
                    'reviewed_at' => $status !== 'pending' ? now()->subDays(rand(1, 7)) : null,
                    'reviewed_by' => $status !== 'pending' ? 1 : null,
                    'rejection_reason' => $status === 'rejected' ? 'Document not clear' : null,
                ]);
            }
        }
    }

    private function createAuditLogs($admin, $users)
    {
        $actionTypes = [
            'approved_payout' => 'payout',
            'rejected_payout' => 'payout',
            'approved_referral' => 'referral',
            'rejected_referral' => 'referral',
            'approved_deposit' => 'transaction',
            'rejected_deposit' => 'transaction',
            'suspended_user' => 'user',
            'unsuspended_user' => 'user',
        ];

        // Create 20 audit logs
        for ($i = 0; $i < 20; $i++) {
            $actionType = array_rand($actionTypes);
            $targetType = $actionTypes[$actionType];

            $targetId = match ($targetType) {
                'payout' => rand(1, 15),
                'referral' => rand(1, 10),
                'transaction' => rand(1, 50),
                'user' => $users[array_rand($users)]->id,
            };

            $details = match ($actionType) {
                'approved_payout' => "Approved payout #{$targetId} for user",
                'rejected_payout' => "Rejected payout #{$targetId} - insufficient funds",
                'approved_referral' => "Approved referral #{$targetId} bonus credited",
                'rejected_referral' => "Rejected referral #{$targetId} - invalid documents",
                'approved_deposit' => "Approved deposit #{$targetId}",
                'rejected_deposit' => "Rejected deposit #{$targetId} - suspicious activity",
                'suspended_user' => "Suspended user #{$targetId} - policy violation",
                'unsuspended_user' => "Unsuspended user #{$targetId} - issue resolved",
            };

            AuditLog::create([
                'admin_id' => $admin->id,
                'action_type' => $actionType,
                'target_id' => $targetId,
                'target_type' => $targetType,
                'details' => $details,
                'ip_address' => '192.168.1.' . rand(10, 255),
                'created_at' => now()->subDays(rand(0, 7)),
            ]);
        }
    }
}
