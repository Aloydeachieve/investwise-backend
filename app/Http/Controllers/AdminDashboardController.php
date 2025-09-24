<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transaction;
use App\Models\Investment;
use App\Models\Payout;
use App\Models\Referral;
use App\Models\KycSubmission;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function summary()
    {
        // User Statistics
        $totalUsers = User::count();
        $activeUsers = User::where('status', 'active')->count();
        $suspendedUsers = User::where('status', 'suspended')->count();

        // Transaction Statistics
        $totalDeposits = Transaction::where('type', 'deposit');
        $pendingDeposits = (clone $totalDeposits)->where('status', 'pending')->sum('amount');
        $approvedDeposits = (clone $totalDeposits)->where('status', 'approved')->sum('amount');
        $rejectedDeposits = (clone $totalDeposits)->where('status', 'rejected')->sum('amount');

        $totalWithdrawals = Transaction::where('type', 'withdrawal');
        $pendingWithdrawals = (clone $totalWithdrawals)->where('status', 'pending')->sum('amount');
        $approvedWithdrawals = (clone $totalWithdrawals)->where('status', 'approved')->sum('amount');
        $rejectedWithdrawals = (clone $totalWithdrawals)->where('status', 'rejected')->sum('amount');

        $totalPayouts = Payout::query();
        $pendingPayouts = (clone $totalPayouts)->where('status', 'pending')->sum('amount');
        $approvedPayouts = (clone $totalPayouts)->where('status', 'approved')->sum('amount');
        $rejectedPayouts = (clone $totalPayouts)->where('status', 'rejected')->sum('amount');

        // Investment Statistics
        $totalInvestments = Investment::query();
        $activeInvestments = (clone $totalInvestments)->where('status', 'active')->sum('amount');
        $maturedInvestments = (clone $totalInvestments)->where('status', 'matured')->sum('amount');
        $closedInvestments = (clone $totalInvestments)->where('status', 'closed')->sum('amount');

        // Referral Statistics
        $totalReferralBonuses = Transaction::where('type', 'referral_bonus')->sum('amount');

        // Wallet Balance (sum across all users)
        $totalWalletBalance = User::sum('wallet_balance');

        // KYC Statistics
        $kycPending = KycSubmission::where('status', 'pending')->count();
        $kycApproved = KycSubmission::where('status', 'approved')->count();
        $kycRejected = KycSubmission::where('status', 'rejected')->count();

        return response()->json([
            'users' => [
                'total' => $totalUsers,
                'active' => $activeUsers,
                'suspended' => $suspendedUsers,
            ],
            'deposits' => [
                'pending' => number_format($pendingDeposits, 2),
                'approved' => number_format($approvedDeposits, 2),
                'rejected' => number_format($rejectedDeposits, 2),
                'total' => number_format($approvedDeposits + $pendingDeposits + $rejectedDeposits, 2),
            ],
            'withdrawals' => [
                'pending' => number_format($pendingWithdrawals, 2),
                'approved' => number_format($approvedWithdrawals, 2),
                'rejected' => number_format($rejectedWithdrawals, 2),
                'total' => number_format($approvedWithdrawals + $pendingWithdrawals + $rejectedWithdrawals, 2),
            ],
            'payouts' => [
                'pending' => number_format($pendingPayouts, 2),
                'approved' => number_format($approvedPayouts, 2),
                'rejected' => number_format($rejectedPayouts, 2),
                'total' => number_format($approvedPayouts + $pendingPayouts + $rejectedPayouts, 2),
            ],
            'investments' => [
                'active' => number_format($activeInvestments, 2),
                'matured' => number_format($maturedInvestments, 2),
                'closed' => number_format($closedInvestments, 2),
                'total' => number_format($activeInvestments + $maturedInvestments + $closedInvestments, 2),
            ],
            'referrals' => [
                'total_bonuses' => number_format($totalReferralBonuses, 2),
            ],
            'wallet_balance' => number_format($totalWalletBalance, 2),
            'kyc' => [
                'pending' => $kycPending,
                'approved' => $kycApproved,
                'rejected' => $kycRejected,
            ],
        ]);
    }

    public function users()
    {
        // Top 5 most active users (by total transaction volume)
        $topActiveUsers = User::select('users.id', 'users.name', 'users.email', DB::raw('SUM(transactions.amount) as total_volume'))
            ->join('transactions', 'users.id', '=', 'transactions.user_id')
            ->where('transactions.status', 'approved')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderBy('total_volume', 'desc')
            ->limit(5)
            ->get();

        // Recent registrations (last 7 days)
        $recentRegistrations = User::where('created_at', '>=', Carbon::now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->get();

        // KYC verification counts
        $kycStats = [
            'pending' => KycSubmission::where('status', 'pending')->count(),
            'approved' => KycSubmission::where('status', 'approved')->count(),
            'rejected' => KycSubmission::where('status', 'rejected')->count(),
        ];

        return response()->json([
            'top_active_users' => $topActiveUsers,
            'recent_registrations' => $recentRegistrations,
            'kyc_stats' => $kycStats,
        ]);
    }

    public function finance(Request $request)
    {
        $period = $request->get('period', 'daily'); // daily, weekly, monthly
        $days = $this->getPeriodDays($period);

        // Deposits over time
        $deposits = Transaction::select(
                DB::raw('DATE(transaction_date) as date'),
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->where('type', 'deposit')
            ->where('status', 'approved')
            ->where('transaction_date', '>=', Carbon::now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Withdrawals over time
        $withdrawals = Transaction::select(
                DB::raw('DATE(transaction_date) as date'),
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->where('type', 'withdrawal')
            ->where('status', 'approved')
            ->where('transaction_date', '>=', Carbon::now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Referral bonuses over time
        $referralBonuses = Transaction::select(
                DB::raw('DATE(transaction_date) as date'),
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->where('type', 'referral_bonus')
            ->where('status', 'approved')
            ->where('transaction_date', '>=', Carbon::now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Platform profit calculation (deposits - withdrawals - payouts)
        $totalDeposits = Transaction::where('type', 'deposit')->where('status', 'approved')->sum('amount');
        $totalWithdrawals = Transaction::where('type', 'withdrawal')->where('status', 'approved')->sum('amount');
        $totalPayouts = Payout::where('status', 'approved')->sum('amount');
        $platformProfit = $totalDeposits - $totalWithdrawals - $totalPayouts;

        return response()->json([
            'deposits' => $deposits,
            'withdrawals' => $withdrawals,
            'referral_bonuses' => $referralBonuses,
            'platform_profit' => number_format($platformProfit, 2),
            'period' => $period,
            'days' => $days,
        ]);
    }

    public function auditLogs(Request $request)
    {
        $query = AuditLog::with('admin:id,name,email')
            ->orderBy('created_at', 'desc');

        // Filter by admin
        if ($request->has('admin_id')) {
            $query->where('admin_id', $request->admin_id);
        }

        // Filter by action type
        if ($request->has('action_type')) {
            $query->where('action_type', $request->action_type);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $logs = $query->paginate($request->get('per_page', 15));

        return response()->json($logs);
    }

    private function getPeriodDays($period)
    {
        return match ($period) {
            'daily' => 30,
            'weekly' => 12, // 12 weeks
            'monthly' => 12, // 12 months
            default => 30,
        };
    }
}
