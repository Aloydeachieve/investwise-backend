<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some users for notifications
        $users = User::take(5)->get();

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please run UserSeeder first.');
            return;
        }

        // Create 10 test user notifications
        $this->createUserNotifications($users);

        // Create 5 test admin notifications
        $this->createAdminNotifications();

        $this->command->info('NotificationSeeder completed successfully!');
        $this->command->info('Created 10 user notifications and 5 admin notifications');
    }

    /**
     * Create test user notifications
     */
    private function createUserNotifications($users): void
    {
        $userNotifications = [
            [
                'title' => 'Deposit Submitted',
                'message' => 'Your deposit of $1,500.00 has been submitted and is pending approval.',
                'type' => 'deposit',
                'meta' => ['amount' => 1500.00, 'event' => 'created']
            ],
            [
                'title' => 'Deposit Approved',
                'message' => 'Your deposit of $2,500.00 has been approved successfully.',
                'type' => 'deposit',
                'meta' => ['amount' => 2500.00, 'event' => 'approved']
            ],
            [
                'title' => 'Withdrawal Request',
                'message' => 'Your withdrawal request of $800.00 has been submitted and is pending approval.',
                'type' => 'withdrawal',
                'meta' => ['amount' => 800.00, 'event' => 'requested']
            ],
            [
                'title' => 'Withdrawal Approved',
                'message' => 'Your withdrawal of $1,200.00 has been approved and processed.',
                'type' => 'withdrawal',
                'meta' => ['amount' => 1200.00, 'event' => 'approved']
            ],
            [
                'title' => 'KYC Submitted',
                'message' => 'Your KYC documents have been submitted successfully and are under review.',
                'type' => 'kyc',
                'meta' => ['event' => 'submitted']
            ],
            [
                'title' => 'KYC Approved',
                'message' => 'Congratulations! Your KYC verification has been approved.',
                'type' => 'kyc',
                'meta' => ['event' => 'approved']
            ],
            [
                'title' => 'Referral Bonus Approved',
                'message' => 'Congratulations! Your referral bonus of $150.00 has been approved and added to your account.',
                'type' => 'referral',
                'meta' => ['amount' => 150.00, 'event' => 'bonus_approved']
            ],
            [
                'title' => 'Payout Request',
                'message' => 'Your payout request of $3,000.00 has been submitted and is pending approval.',
                'type' => 'payout',
                'meta' => ['amount' => 3000.00, 'event' => 'requested']
            ],
            [
                'title' => 'Payout Approved',
                'message' => 'Your payout of $2,500.00 has been approved and processed.',
                'type' => 'payout',
                'meta' => ['amount' => 2500.00, 'event' => 'approved']
            ],
            [
                'title' => 'Investment Created',
                'message' => 'Your investment of $5,000.00 in Premium Plan has been created successfully.',
                'type' => 'investment',
                'meta' => ['amount' => 5000.00, 'plan_name' => 'Premium Plan', 'event' => 'created']
            ]
        ];

        foreach ($userNotifications as $index => $notificationData) {
            Notification::create([
                'user_id' => $users[$index % $users->count()]->id,
                'title' => $notificationData['title'],
                'message' => $notificationData['message'],
                'type' => $notificationData['type'],
                'is_read' => rand(0, 1), // Random read/unread status
                'for_admin' => false,
                'meta' => $notificationData['meta']
            ]);
        }
    }

    /**
     * Create test admin notifications
     */
    private function createAdminNotifications(): void
    {
        $adminNotifications = [
            [
                'title' => 'New Deposit Pending',
                'message' => 'User John Doe has submitted a deposit of $2,500.00 and requires approval.',
                'type' => 'deposit',
                'meta' => ['user_name' => 'John Doe', 'amount' => 2500.00, 'user_id' => 1]
            ],
            [
                'title' => 'New KYC Submission',
                'message' => 'User Jane Smith has submitted KYC documents for verification.',
                'type' => 'kyc',
                'meta' => ['user_name' => 'Jane Smith', 'user_id' => 2]
            ],
            [
                'title' => 'Withdrawal Request',
                'message' => 'User Mike Johnson has requested a withdrawal of $1,500.00.',
                'type' => 'withdrawal',
                'meta' => ['user_name' => 'Mike Johnson', 'amount' => 1500.00, 'user_id' => 3]
            ],
            [
                'title' => 'Payout Request',
                'message' => 'User Sarah Wilson has requested a payout of $4,000.00.',
                'type' => 'payout',
                'meta' => ['user_name' => 'Sarah Wilson', 'amount' => 4000.00, 'user_id' => 4]
            ],
            [
                'title' => 'System Maintenance',
                'message' => 'Scheduled system maintenance will occur tonight from 2:00 AM to 4:00 AM EST.',
                'type' => 'system',
                'meta' => ['maintenance_window' => '2:00 AM - 4:00 AM EST']
            ]
        ];

        foreach ($adminNotifications as $notificationData) {
            Notification::create([
                'user_id' => null,
                'title' => $notificationData['title'],
                'message' => $notificationData['message'],
                'type' => $notificationData['type'],
                'is_read' => rand(0, 1), // Random read/unread status
                'for_admin' => true,
                'meta' => $notificationData['meta']
            ]);
        }
    }
}
