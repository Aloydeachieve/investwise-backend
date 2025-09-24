<?php

namespace App\Policies;

use App\Models\KycSubmission;
use App\Models\User;

class KycSubmissionPolicy
{
    /**
     * Determine whether the user can view any KYC submissions.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can view the KYC submission.
     */
    public function view(User $user, KycSubmission $kycSubmission): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can create KYC submissions.
     */
    public function create(User $user): bool
    {
        return $user->is_authenticated();
    }

    /**
     * Determine whether the user can update the KYC submission.
     */
    public function update(User $user, KycSubmission $kycSubmission): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can delete the KYC submission.
     */
    public function delete(User $user, KycSubmission $kycSubmission): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can approve the KYC submission.
     */
    public function approve(User $user, KycSubmission $kycSubmission): bool
    {
        return $user->role === 'admin' && $kycSubmission->status === 'pending';
    }

    /**
     * Determine whether the user can reject the KYC submission.
     */
    public function reject(User $user, KycSubmission $kycSubmission): bool
    {
        return $user->role === 'admin' && $kycSubmission->status === 'pending';
    }

    /**
     * Determine whether the user can view their own KYC submissions.
     */
    public function viewOwn(User $user, KycSubmission $kycSubmission): bool
    {
        return $user->id === $kycSubmission->user_id;
    }

    /**
     * Determine whether the user can download KYC documents.
     */
    public function download(User $user, KycSubmission $kycSubmission): bool
    {
        return $user->role === 'admin' || $user->id === $kycSubmission->user_id;
    }
}
