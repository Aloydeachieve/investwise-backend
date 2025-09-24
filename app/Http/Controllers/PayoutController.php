<?php

namespace App\Http\Controllers;

use App\Models\Payout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayoutController extends Controller
{
    public function request(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|string',
        ]);

        return DB::transaction(function () use ($request) {
            $user = Auth::user();

            // Check if user has sufficient balance
            $availableBalance = $user->wallet_balance;

            if ($request->amount > $availableBalance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance. Available: $' . number_format($availableBalance, 2)
                ], 422);
            }

            $payout = Payout::create([
                'user_id' => $user->id,
                'amount' => $request->amount,
                'method' => $request->method,
                'status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payout request created successfully',
                'data' => $payout->load('user')
            ], 201);
        });
    }

    public function history()
    {
        $payouts = Payout::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $payouts
        ]);
    }
}
