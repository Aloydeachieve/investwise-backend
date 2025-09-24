<?php

namespace Database\Seeders;

use App\Models\KycSubmission;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class KycSubmissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all users and admin user
        $users = User::where('role', '!=', 'admin')->get();
        $admin = User::where('role', 'admin')->first();

        if ($users->isEmpty() || !$admin) {
            $this->command->warn('No users or admin found. Please run AdminUserSeeder first.');
            return;
        }

        // Create sample KYC submissions with different statuses
        $documentTypes = ['passport', 'driver_license', 'national_id', 'proof_of_address', 'utility_bill', 'bank_statement'];
        $statuses = ['pending', 'approved', 'rejected'];

        foreach ($users->take(10) as $user) { // Create submissions for first 10 users
            // Create 1-3 submissions per user
            $submissionCount = rand(1, 3);

            for ($i = 0; $i < $submissionCount; $i++) {
                $documentType = $documentTypes[array_rand($documentTypes)];
                $status = $statuses[array_rand($statuses)];

                // Generate a fake file path (in real scenario, this would be actual uploaded files)
                $filename = 'sample_' . $documentType . '_' . $user->id . '.pdf';
                $filePath = 'kyc_documents/' . $filename;

                // Create submission
                $submission = KycSubmission::create([
                    'user_id' => $user->id,
                    'document_type' => $documentType,
                    'document_file' => $filePath,
                    'status' => $status,
                    'submitted_at' => now()->subDays(rand(1, 30)),
                    'reviewed_at' => $status !== 'pending' ? now()->subDays(rand(1, 15)) : null,
                    'reviewed_by' => $status !== 'pending' ? $admin->id : null,
                    'rejection_reason' => $status === 'rejected' ? $this->getRandomRejectionReason() : null,
                ]);
            }
        }

        $this->command->info('KYC submissions seeded successfully!');
        $this->command->info('Created ' . KycSubmission::count() . ' KYC submissions.');
    }

    /**
     * Get a random rejection reason.
     */
    private function getRandomRejectionReason(): string
    {
        $reasons = [
            'Document quality is poor and unclear',
            'Document has expired',
            'Information does not match user profile',
            'Document appears to be altered or tampered with',
            'Missing required security features',
            'Document type not acceptable for verification',
            'Unable to verify document authenticity',
        ];

        return $reasons[array_rand($reasons)];
    }
}
