<?php

namespace App\Http\Controllers;

use App\Models\Investment;
use App\Models\Transaction;
use App\Models\Referral;
use App\Models\Payout;
use App\Models\KycSubmission;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get dashboard overview with key financial stats
     */
    public function overview()
    {
        $user = Auth::user();

        // Calculate wallet balance using the User's attribute accessor
        $walletBalance = $user->wallet_balance;

        // Total deposits
        $totalDeposits = $user->transactions()
            ->where('type', 'deposit')
            ->where('status', 'approved')
            ->sum('amount');

        // Total withdrawals
        $totalWithdrawals = $user->transactions()
            ->where('type', 'withdrawal')
            ->where('status', 'approved')
            ->sum('amount');

        // Active investments
        $activeInvestments = $user->investments()
            ->where('status', 'active')
            ->with('plan:id,name');

        $activeInvestmentsCount = $activeInvestments->count();
        $activeInvestmentsValue = $activeInvestments->sum('amount_invested');

        // Referral bonus earned
        $referralBonus = $user->transactions()
            ->where('type', 'referral_bonus')
            ->where('status', 'approved')
            ->sum('amount');

        // Pending payouts
        $pendingPayouts = $user->payouts()
            ->where('status', 'pending')
            ->count();

        // KYC status
        $kycStatus = $user->kycSubmission ? $user->kycSubmission->status : 'not_submitted';

        return response()->json([
            'wallet_balance' => $walletBalance,
            'total_deposits' => $totalDeposits,
            'total_withdrawals' => $totalWithdrawals,
            'active_investments' => [
                'count' => $activeInvestmentsCount,
                'total_value' => $activeInvestmentsValue
            ],
            'referral_bonus' => $referralBonus,
            'pending_payouts' => $pendingPayouts,
            'kyc_status' => $kycStatus
        ]);
    }

    /**
     * Get recent transactions
     */
    public function recentTransactions()
    {
        $user = Auth::user();

        $transactions = $user->transactions()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['type', 'amount', 'status', 'reference', 'created_at']);

        return response()->json([
            'transactions' => $transactions
        ]);
    }

    /**
     * Get investments summary with performance data
     */
    public function investmentsSummary()
    {
        $user = Auth::user();

        $investments = $user->investments()
            ->where('status', 'active')
            ->with('plan:id,name')
            ->get()
            ->map(function ($investment) {
                $startDate = Carbon::parse($investment->start_date);
                $endDate = Carbon::parse($investment->end_date);
                $now = Carbon::now();

                // Calculate maturity progress percentage
                $totalDuration = $startDate->diffInDays($endDate);
                $elapsed = $startDate->diffInDays($now);
                $progress = $totalDuration > 0 ? min(($elapsed / $totalDuration) * 100, 100) : 0;

                // Calculate profit earned so far (simplified calculation)
                $dailyProfit = $investment->profit_expected / $totalDuration;
                $profitEarned = $dailyProfit * $elapsed;

                return [
                    'id' => $investment->id,
                    'plan_name' => $investment->plan->name,
                    'amount_invested' => $investment->amount_invested,
                    'profit_expected' => $investment->profit_expected,
                    'profit_earned' => min($profitEarned, $investment->profit_expected),
                    'start_date' => $investment->start_date,
                    'end_date' => $investment->end_date,
                    'maturity_progress' => round($progress, 2),
                    'status' => $investment->status
                ];
            });

        return response()->json([
            'investments' => $investments
        ]);
    }

    /**
     * Get referrals summary
     */
    public function referralsSummary()
    {
        $user = Auth::user();

        $totalInvited = $user->referralsMade()->count();

        $confirmedReferrals = $user->referralsMade()
            ->where('status', 'confirmed')
            ->count();

        $totalBonusEarned = $user->transactions()
            ->where('type', 'referral_bonus')
            ->where('status', 'approved')
            ->sum('amount');

        return response()->json([
            'total_invited' => $totalInvited,
            'confirmed_referrals' => $confirmedReferrals,
            'total_bonus_earned' => $totalBonusEarned
        ]);
    }

    /**
     * Get activity log (MVP+ feature)
     */
    public function activityLog()
    {
        $user = Auth::user();

        $activities = AuditLog::where('admin_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get(['action_type', 'ip_address', 'created_at']);

        return response()->json([
            'activities' => $activities
        ]);
    }
}
