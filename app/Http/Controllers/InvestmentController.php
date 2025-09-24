<?php

namespace App\Http\Controllers;

use App\Models\Investment;
use App\Models\Plan;
use App\Models\Transaction;
use App\Http\Requests\CreateInvestmentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InvestmentController extends Controller
{
    public function index()
    {
        $investments = Investment::with('plan')
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $investments
        ]);
    }

    public function store(CreateInvestmentRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $user = Auth::user();
            $plan = Plan::findOrFail($request->plan_id);

            // Validate amount against plan limits
            if ($request->amount < $plan->min_deposit || $request->amount > $plan->max_deposit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Investment amount must be between $' . $plan->min_deposit . ' and $' . $plan->max_deposit
                ], 422);
            }

            // Calculate expected profit
            $profitExpected = ($request->amount * $plan->profit_rate / 100) * ($plan->duration_days / 365);

            // Calculate end date
            $startDate = Carbon::now();
            $endDate = Carbon::now()->addDays($plan->duration_days);

            // Create investment
            $investment = Investment::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'amount_invested' => $request->amount,
                'profit_expected' => $profitExpected,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            // Create deposit transaction
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'type' => 'deposit',
                'amount' => $request->amount,
                'status' => 'pending',
                'transaction_date' => $startDate,
            ]);
            $transaction->generateReference();

            return response()->json([
                'success' => true,
                'message' => 'Investment created successfully. Awaiting deposit approval.',
                'data' => [
                    'investment' => $investment->load('plan'),
                    'transaction' => $transaction
                ]
            ], 201);
        });
    }
}
