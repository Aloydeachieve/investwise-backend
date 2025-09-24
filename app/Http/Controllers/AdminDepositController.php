<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\NotificationService;
use App\Http\Requests\ApproveDepositRequest;
use App\Http\Requests\RejectDepositRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDepositController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->middleware('auth:sanctum');
        $this->middleware('admin');
        $this->notificationService = $notificationService;
    }
    public function pending()
    {
        $deposits = Transaction::with('user')
            ->where('type', 'deposit')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $deposits
        ]);
    }

    public function approve(ApproveDepositRequest $request, $id)
    {
        return DB::transaction(function () use ($id) {
            $transaction = Transaction::findOrFail($id);

            if ($transaction->type !== 'deposit' || $transaction->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found or already processed'
                ], 404);
            }

            $transaction->update(['status' => 'approved']);

            // Create notification for user
            $this->notificationService->notifyDepositEvent(
                $transaction->user,
                'approved',
                [
                    'amount' => $transaction->amount,
                    'transaction_id' => $transaction->id
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Deposit approved successfully',
                'data' => $transaction->load('user')
            ]);
        });
    }

    public function reject(RejectDepositRequest $request, $id)
    {
        return DB::transaction(function () use ($id, $request) {
            $transaction = Transaction::findOrFail($id);

            if ($transaction->type !== 'deposit' || $transaction->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found or already processed'
                ], 404);
            }

            $transaction->update(['status' => 'rejected']);

            // Create notification for user
            $this->notificationService->notifyDepositEvent(
                $transaction->user,
                'rejected',
                [
                    'amount' => $transaction->amount,
                    'transaction_id' => $transaction->id,
                    'reason' => $request->get('reason', 'Not specified')
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Deposit rejected successfully',
                'data' => $transaction->load('user')
            ]);
        });
    }
}
