<?php

namespace App\Http\Controllers;

use App\Models\Referral;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReferralController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        $referrals = Referral::with(['referred'])
            ->where('referrer_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $formattedReferrals = $referrals->getCollection()->map(function ($referral) {
            return [
                'id' => $referral->id,
                'referred_user' => [
                    'id' => $referral->referred->id,
                    'name' => $referral->referred->name,
                    'email' => $referral->referred->email,
                ],
                'bonus_amount' => $referral->bonus_amount,
                'status' => $referral->status,
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

    public function stats(): JsonResponse
    {
        $userId = auth()->id();

        $totalInvited = Referral::where('referrer_id', $userId)->count();
        $confirmedReferrals = Referral::where('referrer_id', $userId)
            ->where('status', 'confirmed')
            ->count();
        $pendingReferrals = Referral::where('referrer_id', $userId)
            ->where('status', 'pending')
            ->count();
        $cancelledReferrals = Referral::where('referrer_id', $userId)
            ->where('status', 'cancelled')
            ->count();
        $totalBonusEarned = Referral::where('referrer_id', $userId)
            ->where('status', 'confirmed')
            ->sum('bonus_amount');

        return response()->json([
            'success' => true,
            'data' => [
                'total_invited' => $totalInvited,
                'confirmed_referrals' => $confirmedReferrals,
                'pending_referrals' => $pendingReferrals,
                'cancelled_referrals' => $cancelledReferrals,
                'total_bonus_earned' => $totalBonusEarned,
            ]
        ]);
    }
}
