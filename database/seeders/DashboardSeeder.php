<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Investment;
use App\Models\Transaction;
use App\Models\Referral;
use App\Models\Payout;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Plan;
use Carbon\Carbon;

class DashboardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all users and plans
        $users = User::where('role', '!=', 'admin')->get();
        $plans = Plan::all();

        if ($users->isEmpty() || $plans->isEmpty()) {
            return; // No users or plans to create dashboard data for
        }

        $this->createInvestments($users, $plans);
        $this->createReferralBonuses($users);
        $this->createAuditLogs($users);
    }

    /**
     * Create sample investments for dashboard testing
     */
    private function createInvestments($users, $plans)
    {
        $investments = [];

        foreach ($users as $user) {
            // Create 2-4 investments per user
            $investmentCount = rand(2, 4);

            for ($i = 0; $i < $investmentCount; $i++) {
                $plan = $plans->random();
                $amount = rand(1000, 10000);
                $startDate = Carbon::now()->subDays(rand(1, 60));
                $duration = $plan->duration_days;
                $endDate = $startDate->copy()->addDays($duration);

                // Randomly set some investments as completed
                $status = rand(1, 10) <= 7 ? 'active' : 'completed';

                $investments[] = [
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'amount_invested' => $amount,
                    'profit_expected' => $amount * ($plan->profit_percentage / 100),
                    'status' => $status,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'created_at' => $startDate,
                    'updated_at' => Carbon::now(),
                ];
            }
        }

        // Insert investments in chunks
        foreach (array_chunk($investments, 50) as $chunk) {
            Investment::insert($chunk);
        }
    }

    /**
     * Create referral bonus transactions
     */
    private function createReferralBonuses($users)
    {
        $transactions = [];

        foreach ($users as $user) {
            // Create 1-3 referral bonuses per user
            $bonusCount = rand(1, 3);

            for ($i = 0; $i < $bonusCount; $i++) {
                $transactions[] = [
                    'user_id' => $user->id,
                    'type' => 'referral_bonus',
                    'amount' => rand(50, 500),
                    'status' => 'approved',
                    'transaction_date' => Carbon::now()->subDays(rand(1, 30)),
                    'reference' => 'TXN' . date('Ymd') . strtoupper(uniqid()),
                    'created_at' => Carbon::now()->subDays(rand(1, 30)),
                    'updated_at' => Carbon::now()->subDays(rand(1, 30)),
                ];
            }
        }

        // Insert referral bonus transactions
        foreach (array_chunk($transactions, 50) as $chunk) {
            Transaction::insert($chunk);
        }
    }

    /**
     * Create sample audit logs for activity tracking
     */
    private function createAuditLogs($users)
    {
        $auditLogs = [];
        $actions = ['login', 'logout', 'password_change', 'profile_update', 'investment_created', 'withdrawal_requested'];
        $browsers = ['Chrome/91.0', 'Firefox/89.0', 'Safari/14.1', 'Edge/91.0'];
        $ipAddresses = ['192.168.1.100', '10.0.0.50', '172.16.0.25', '203.0.113.10'];

        foreach ($users as $user) {
            // Create 5-15 audit logs per user
            $logCount = rand(5, 15);

            for ($i = 0; $i < $logCount; $i++) {
        $auditLogs[] = [
                    'admin_id' => $user->id,
                    'action_type' => $actions[array_rand($actions)],
                    'ip_address' => $ipAddresses[array_rand($ipAddresses)],
                    'created_at' => Carbon::now()->subDays(rand(1, 30)),
                    'updated_at' => Carbon::now()->subDays(rand(1, 30)),
                ];
            }
        }

        // Insert audit logs in chunks
        foreach (array_chunk($auditLogs, 100) as $chunk) {
            AuditLog::insert($chunk);
        }
    }
}
