<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Http\Requests\CreateDepositRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DepositController extends Controller
{
    public function store(CreateDepositRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $user = Auth::user();

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'type' => 'deposit',
                'amount' => $request->amount,
                'status' => 'pending',
                'transaction_date' => now(),
            ]);

            $transaction->generateReference();

            return response()->json([
                'success' => true,
                'message' => 'Deposit request created successfully',
                'data' => $transaction
            ], 201);
        });
    }

    public function history()
    {
        $transactions = Transaction::where('user_id', Auth::id())
            ->where('type', 'deposit')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }
}
