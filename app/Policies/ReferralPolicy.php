<?php

namespace App\Policies;

use App\Models\Referral;
use App\Models\User;

class ReferralPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine whether the user can view any referrals.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can view the referral.
     */
    public function view(User $user, Referral $referral): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can create referrals.
     */
    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can update the referral.
     */
    public function update(User $user, Referral $referral): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can delete the referral.
     */
    public function delete(User $user, Referral $referral): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can approve referrals.
     */
    public function approve(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can reject referrals.
     */
    public function reject(User $user): bool
    {
        return $user->role === 'admin';
    }
}
