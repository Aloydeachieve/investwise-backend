<?php

namespace App\Http\Controllers;

use App\Models\Referral;
use App\Models\Transaction;
use App\Models\AuditLog;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\ApproveReferralRequest;
use App\Http\Requests\RejectReferralRequest;

class AdminReferralController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->middleware('auth:sanctum');
        $this->middleware('admin');
        $this->notificationService = $notificationService;
    }
    public function pending(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        $pendingReferrals = Referral::with(['referrer', 'referred'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $formattedReferrals = $pendingReferrals->getCollection()->map(function ($referral) {
            return [
                'id' => $referral->id,
                'referrer' => [
                    'id' => $referral->referrer->id,
                    'name' => $referral->referrer->name,
                    'email' => $referral->referrer->email,
                ],
                'referred' => [
                    'id' => $referral->referred->id,
                    'name' => $referral->referred->name,
                    'email' => $referral->referred->email,
                ],
                'bonus_amount' => $referral->bonus_amount,
                'created_at' => $referral->created_at->toISOString(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'referrals' => $formattedReferrals,
                'pagination' => [
                    'current_page' => $pendingReferrals->currentPage(),
                    'last_page' => $pendingReferrals->lastPage(),
                    'per_page' => $pendingReferrals->perPage(),
                    'total' => $pendingReferrals->total(),
                ]
            ]
        ]);
    }

    public function confirmed(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        $confirmedReferrals = Referral::with(['referrer', 'referred'])
            ->where('status', 'confirmed')
            ->orderBy('updated_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $formattedReferrals = $confirmedReferrals->getCollection()->map(function ($referral) {
            return [
                'id' => $referral->id,
                'referrer' => [
                    'id' => $referral->referrer->id,
                    'name' => $referral->referrer->name,
                    'email' => $referral->referrer->email,
                ],
                'referred' => [
                    'id' => $referral->referred->id,
                    'name' => $referral->referred->name,
                    'email' => $referral->referred->email,
                ],
                'bonus_amount' => $referral->bonus_amount,
                'created_at' => $referral->created_at->toISOString(),
                'updated_at' => $referral->updated_at->toISOString(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'referrals' => $formattedReferrals,
                'pagination' => [
                    'current_page' => $confirmedReferrals->currentPage(),
                    'last_page' => $confirmedReferrals->lastPage(),
                    'per_page' => $confirmedReferrals->perPage(),
                    'total' => $confirmedReferrals->total(),
                ]
            ]
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        $referrals = Referral::with(['referrer', 'referred'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $formattedReferrals = $referrals->getCollection()->map(function ($referral) {
            return [
                'id' => $referral->id,
                'referrer' => [
                    'id' => $referral->referrer->id,
                    'name' => $referral->referrer->name,
                    'email' => $referral->referrer->email,
                ],
                'referred' => [
                    'id' => $referral->referred->id,
                    'name' => $referral->referred->name,
                    'email' => $referral->referred->email,
                ],
                'bonus_amount' => $referral->bonus_amount,
                'status' => $referral->status,
                'notes' => $referral->notes,
                'created_at' => $referral->created_at->toISOString(),
                'updated_at' => $referral->updated_at->toISOString(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'referrals' => $formattedReferrals,
                'pagination' => [
                    'current_page' => $referrals->currentPage(),
                    'last_page' => $referrals->lastPage(),
                    'per_page' => $referrals->perPage(),
                    'total' => $referrals->total(),
                ]
            ]
        ]);
    }

    public function show($id): JsonResponse
    {
        $referral = Referral::with(['referrer', 'referred'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $referral->id,
                'referrer' => [
                    'id' => $referral->referrer->id,
                    'name' => $referral->referrer->name,
                    'email' => $referral->referrer->email,
                ],
                'referred' => [
                    'id' => $referral->referred->id,
                    'name' => $referral->referred->name,
                    'email' => $referral->referred->email,
                ],
                'bonus_amount' => $referral->bonus_amount,
                'status' => $referral->status,
                'notes' => $referral->notes,
                'created_at' => $referral->created_at->toISOString(),
                'updated_at' => $referral->updated_at->toISOString(),
            ]
        ]);
    }

    public function approve(ApproveReferralRequest $request, $id): JsonResponse
    {
        $referral = Referral::findOrFail($id);

        if ($referral->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending referrals can be approved'
            ], 400);
        }

        DB::transaction(function () use ($referral, $request) {
            // Update referral status
            $referral->update([
                'status' => 'confirmed',
                'notes' => $request->notes,
            ]);

            // Create transaction for referrer's bonus
            Transaction::create([
                'user_id' => $referral->referrer_id,
                'type' => 'referral_bonus',
                'amount' => $referral->bonus_amount,
                'status' => 'completed',
                'transaction_date' => now(),
            ]);

            // Create notification for referrer
            $this->notificationService->notifyReferralEvent(
                $referral->referrer,
                'bonus_approved',
                [
                    'amount' => $referral->bonus_amount,
                    'referral_id' => $referral->id,
                    'referred_user' => $referral->referred->name
                ]
            );
        });

        // Log the admin action
        AuditLog::log(
            auth()->id(),
            'approved_referral',
            $referral->id,
            'referral',
            "Approved referral #{$referral->id} from {$referral->referrer->name} to {$referral->referred->name} (Bonus: {$referral->bonus_amount})"
        );

        return response()->json([
            'success' => true,
            'message' => 'Referral approved successfully and bonus credited',
            'data' => [
                'id' => $referral->id,
                'status' => $referral->status,
                'bonus_amount' => $referral->bonus_amount,
                'credited_to' => $referral->referrer->name,
            ]
        ]);
    }

    public function reject(RejectReferralRequest $request, $id): JsonResponse
    {
        $referral = Referral::findOrFail($id);

        if ($referral->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending referrals can be rejected'
            ], 400);
        }

        $referral->update([
            'status' => 'cancelled',
            'notes' => $request->notes,
        ]);

        // Log the admin action
        AuditLog::log(
            auth()->id(),
            'rejected_referral',
            $referral->id,
            'referral',
            "Rejected referral #{$referral->id} from {$referral->referrer->name} to {$referral->referred->name} (Reason: {$request->notes})"
        );

        return response()->json([
            'success' => true,
            'message' => 'Referral rejected successfully',
            'data' => [
                'id' => $referral->id,
                'status' => $referral->status,
                'notes' => $referral->notes,
            ]
        ]);
    }
}
