<?php

namespace App\Services;

use App\Events\NotificationEvent;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Create a notification for a specific user
     */
    public function createUserNotification(
        User $user,
        string $title,
        string $message,
        string $type,
        array $meta = []
    ): Notification {
        try {
            $notification = Notification::create([
                'user_id' => $user->id,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'is_read' => false,
                'for_admin' => false,
                'meta' => $meta
            ]);

            // Broadcast notification to user
            $unreadCount = $this->getUnreadCount($user);
            broadcast(new NotificationEvent($notification, $unreadCount));

            return $notification;
        } catch (\Exception $e) {
            Log::error('Failed to create user notification', [
                'user_id' => $user->id,
                'title' => $title,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create a notification for admins
     */
    public function createAdminNotification(
        string $title,
        string $message,
        string $type,
        array $meta = []
    ): Notification {
        try {
            $notification = Notification::create([
                'user_id' => null,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'is_read' => false,
                'for_admin' => true,
                'meta' => $meta
            ]);

            // Broadcast notification to admins
            $unreadCount = $this->getAdminUnreadCount();
            broadcast(new NotificationEvent($notification, $unreadCount));

            return $notification;
        } catch (\Exception $e) {
            Log::error('Failed to create admin notification', [
                'title' => $title,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create notification for deposit events
     */
    public function notifyDepositEvent(User $user, string $event, array $depositData): Notification
    {
        $messages = [
            'created' => "Your deposit of $" . number_format($depositData['amount'], 2) . " has been submitted and is pending approval.",
            'approved' => "Your deposit of $" . number_format($depositData['amount'], 2) . " has been approved successfully.",
            'rejected' => "Your deposit of $" . number_format($depositData['amount'], 2) . " has been rejected. Reason: " . ($depositData['reason'] ?? 'Not specified')
        ];

        return $this->createUserNotification(
            $user,
            "Deposit " . ucfirst($event),
            $messages[$event] ?? "Your deposit has been " . $event,
            'deposit',
            array_merge($depositData, ['event' => $event])
        );
    }

    /**
     * Create notification for withdrawal events
     */
    public function notifyWithdrawalEvent(User $user, string $event, array $withdrawalData): Notification
    {
        $messages = [
            'requested' => "Your withdrawal request of $" . number_format($withdrawalData['amount'], 2) . " has been submitted and is pending approval.",
            'approved' => "Your withdrawal of $" . number_format($withdrawalData['amount'], 2) . " has been approved and processed.",
            'rejected' => "Your withdrawal request of $" . number_format($withdrawalData['amount'], 2) . " has been rejected. Reason: " . ($withdrawalData['reason'] ?? 'Not specified')
        ];

        return $this->createUserNotification(
            $user,
            "Withdrawal " . ucfirst($event),
            $messages[$event] ?? "Your withdrawal has been " . $event,
            'withdrawal',
            array_merge($withdrawalData, ['event' => $event])
        );
    }

    /**
     * Create notification for KYC events
     */
    public function notifyKycEvent(User $user, string $event, array $kycData = []): Notification
    {
        $messages = [
            'submitted' => "Your KYC documents have been submitted successfully and are under review.",
            'approved' => "Congratulations! Your KYC verification has been approved.",
            'rejected' => "Your KYC verification has been rejected. Please resubmit with correct documents."
        ];

        return $this->createUserNotification(
            $user,
            "KYC " . ucfirst($event),
            $messages[$event] ?? "Your KYC status has been " . $event,
            'kyc',
            array_merge($kycData, ['event' => $event])
        );
    }

    /**
     * Create notification for referral events
     */
    public function notifyReferralEvent(User $user, string $event, array $referralData): Notification
    {
        $messages = [
            'bonus_approved' => "Congratulations! Your referral bonus of $" . number_format($referralData['amount'], 2) . " has been approved and added to your account."
        ];

        return $this->createUserNotification(
            $user,
            "Referral Bonus Approved",
            $messages[$event] ?? "Your referral bonus has been " . $event,
            'referral',
            array_merge($referralData, ['event' => $event])
        );
    }

    /**
     * Create notification for payout events
     */
    public function notifyPayoutEvent(User $user, string $event, array $payoutData): Notification
    {
        $messages = [
            'requested' => "Your payout request of $" . number_format($payoutData['amount'], 2) . " has been submitted and is pending approval.",
            'approved' => "Your payout of $" . number_format($payoutData['amount'], 2) . " has been approved and processed.",
            'rejected' => "Your payout request of $" . number_format($payoutData['amount'], 2) . " has been rejected. Reason: " . ($payoutData['reason'] ?? 'Not specified')
        ];

        return $this->createUserNotification(
            $user,
            "Payout " . ucfirst($event),
            $messages[$event] ?? "Your payout has been " . $event,
            'payout',
            array_merge($payoutData, ['event' => $event])
        );
    }

    /**
     * Create notification for investment events
     */
    public function notifyInvestmentEvent(User $user, string $event, array $investmentData): Notification
    {
        $messages = [
            'created' => "Your investment of $" . number_format($investmentData['amount'], 2) . " in " . ($investmentData['plan_name'] ?? 'a plan') . " has been created successfully.",
            'completed' => "Your investment has matured and completed successfully."
        ];

        return $this->createUserNotification(
            $user,
            "Investment " . ucfirst($event),
            $messages[$event] ?? "Your investment has been " . $event,
            'investment',
            array_merge($investmentData, ['event' => $event])
        );
    }

    /**
     * Get unread count for user
     */
    public function getUnreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Get unread count for admin
     */
    public function getAdminUnreadCount(): int
    {
        return Notification::where('for_admin', true)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $notificationId, User $user): bool
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $user->id)
            ->first();

        if ($notification) {
            return $notification->markAsRead();
        }

        return false;
    }

    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    /**
     * Mark notification as read for admin
     */
    public function markAsReadForAdmin(int $notificationId): bool
    {
        $notification = Notification::where('id', $notificationId)
            ->where('for_admin', true)
            ->first();

        if ($notification) {
            return $notification->markAsRead();
        }

        return false;
    }

    /**
     * Mark all notifications as read for admin
     */
    public function markAllAsReadForAdmin(): int
    {
        return Notification::where('for_admin', true)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }
}
