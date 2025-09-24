<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Http\Requests\CreateWithdrawalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WithdrawalController extends Controller
{
    public function store(CreateWithdrawalRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $user = Auth::user();

            // Check if user has sufficient balance
            $totalDeposits = Transaction::where('user_id', $user->id)
                ->where('type', 'deposit')
                ->where('status', 'approved')
                ->sum('amount');

            $totalWithdrawals = Transaction::where('user_id', $user->id)
                ->where('type', 'withdrawal')
                ->where('status', 'approved')
                ->sum('amount');

            $availableBalance = $totalDeposits - $totalWithdrawals;

            if ($request->amount > $availableBalance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance. Available: $' . $availableBalance
                ], 422);
            }

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'type' => 'withdrawal',
                'amount' => $request->amount,
                'status' => 'pending',
                'transaction_date' => now(),
            ]);

            $transaction->generateReference();

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal request created successfully',
                'data' => $transaction
            ], 201);
        });
    }

    public function history()
    {
        $transactions = Transaction::where('user_id', Auth::id())
            ->where('type', 'withdrawal')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }
}
