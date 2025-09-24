<?php

namespace App\Http\Controllers;

use App\Models\Payout;
use App\Models\AuditLog;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPayoutController extends Controller
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
        $payouts = Payout::with('user')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $payouts
        ]);
    }

    public function approved()
    {
        $payouts = Payout::with('user')
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $payouts
        ]);
    }

    public function history()
    {
        $payouts = Payout::with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $payouts
        ]);
    }

    public function approve(Request $request, $id)
    {
        $request->validate([
            'transaction_reference' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $id) {
            $payout = Payout::findOrFail($id);

            if ($payout->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Payout not found or already processed'
                ], 404);
            }

            $payout->update([
                'status' => 'approved',
                'transaction_reference' => $request->transaction_reference,
            ]);

            // Create notification for user
            $this->notificationService->notifyPayoutEvent(
                $payout->user,
                'approved',
                [
                    'amount' => $payout->amount,
                    'payout_id' => $payout->id,
                    'transaction_reference' => $request->transaction_reference
                ]
            );

            // Log the admin action
            AuditLog::log(
                auth()->id(),
                'approved_payout',
                $payout->id,
                'payout',
                "Approved payout #{$payout->id} for user {$payout->user->name} (Amount: {$payout->amount})"
            );

            return response()->json([
                'success' => true,
                'message' => 'Payout approved successfully',
                'data' => $payout->load('user')
            ]);
        });
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'notes' => 'required|string',
        ]);

        return DB::transaction(function () use ($request, $id) {
            $payout = Payout::findOrFail($id);

            if ($payout->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Payout not found or already processed'
                ], 404);
            }

            $payout->update([
                'status' => 'rejected',
                'notes' => $request->notes,
            ]);

            // Create notification for user
            $this->notificationService->notifyPayoutEvent(
                $payout->user,
                'rejected',
                [
                    'amount' => $payout->amount,
                    'payout_id' => $payout->id,
                    'reason' => $request->notes
                ]
            );

            // Log the admin action
            AuditLog::log(
                auth()->id(),
                'rejected_payout',
                $payout->id,
                'payout',
                "Rejected payout #{$payout->id} for user {$payout->user->name} (Amount: {$payout->amount}, Reason: {$request->notes})"
            );

            return response()->json([
                'success' => true,
                'message' => 'Payout rejected successfully',
                'data' => $payout->load('user')
            ]);
        });
    }
}
