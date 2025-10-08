<?php

namespace App\Http\Controllers;

use App\Models\KycSubmission;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\SubmitKycRequest;

class KycController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->middleware('auth:sanctum');
        $this->notificationService = $notificationService;
    }


    // ...existing code...

    public function submit(SubmitKycRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $submittedDocuments = [];

        // Check if user already has pending KYC
        $existingKyc = KycSubmission::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($existingKyc) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a pending KYC application. Please wait for review before submitting a new one.'
            ], 409);
        }

        // Prepare meta data for extra fields
        $meta = [];
        if ($request->has('nin')) {
            $meta['nin'] = $request->nin;
        }
        if ($request->has('dob')) {
            $meta['dob'] = $request->dob;
        }
        if ($request->has('bvn')) {
            $meta['bvn'] = $request->bvn;
        }

        // Handle multiple documents
        $documents = $request->input('documents', []);
        foreach ($documents as $index => $document) {
            $type = strtolower($document['type']);

            // Handle file upload for document types that require it
            $documentFilePath = null;
            if ($type !== 'bvn') {
                $file = $request->file("documents.$index.file");
                if ($file) {
                    // Generate unique filename
                    $filename = time() . '_' . $user->id . '_' . $type . '_' . $index . '.' . $file->getClientOriginalExtension();

                    // Store file in private storage
                    $documentFilePath = $file->storeAs('kyc_documents', $filename, 'private');
                }
            }

            // Create KYC submission record with meta data
            $kycSubmission = KycSubmission::create([
                'user_id' => $user->id,
                'document_type' => $type,
                'document_file' => $documentFilePath,
                'status' => 'pending',
                'meta' => !empty($meta) ? $meta : null,
                'submitted_at' => now(),
            ]);

            $submittedDocuments[] = [
                'id' => $kycSubmission->id,
                'document_type' => $kycSubmission->document_type,
                'status' => $kycSubmission->status,
                'submitted_at' => $kycSubmission->submitted_at->toISOString(),
            ];
        }

        // Create notification for admins
        $this->notificationService->createAdminNotification(
            'New KYC Submission',
            "User {$user->name} has submitted KYC documents for review.",
            'kyc',
            [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'documents_count' => count($submittedDocuments),
                'submission_ids' => array_column($submittedDocuments, 'id')
            ]
        );

        // Refresh user token to ensure continued authentication
        $user->tokens()->delete(); // Delete existing tokens
        $newToken = $user->createToken('auth_token')->plainTextToken;

        // Add KYC status to user object for frontend context
        $userWithKycStatus = $user->toArray();
        $userWithKycStatus['kyc_status'] = 'pending';

        return response()->json([
            'success' => true,
            'message' => 'KYC documents submitted successfully',
            'data' => [
                'documents' => $submittedDocuments,
                'total_submitted' => count($submittedDocuments)
            ],
            'user' => $userWithKycStatus,
            'token' => $newToken
        ], 201);
    }


    public function status(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $kycSubmissions = KycSubmission::where('user_id', $user->id)
            ->orderBy('submitted_at', 'desc')
            ->get()
            ->map(function ($submission) {
                return [
                    'id' => $submission->id,
                    'document_type' => $submission->document_type,
                    'document_file' => $submission->document_file,
                    'document_url' => $submission->document_url,
                    'status' => $submission->status,
                    'meta' => $submission->meta,
                    'submitted_at' => $submission->submitted_at->toISOString(),
                    'reviewed_at' => $submission->reviewed_at?->toISOString(),
                    'rejection_reason' => $submission->rejection_reason,
                    'reviewed_by' => $submission->reviewer?->name,
                ];
            });

        // Determine overall KYC status
        $overallStatus = 'not_submitted';
        if ($kycSubmissions->isNotEmpty()) {
            $hasApproved = $kycSubmissions->contains('status', 'approved');
            $hasPending = $kycSubmissions->contains('status', 'pending');
            $hasRejected = $kycSubmissions->contains('status', 'rejected');

            if ($hasApproved) {
                $overallStatus = 'approved';
            } elseif ($hasPending) {
                $overallStatus = 'pending';
            } elseif ($hasRejected) {
                $overallStatus = 'rejected';
            }
        }

        // Refresh user token to ensure continued authentication
        $newToken = $user->createToken('auth_token')->plainTextToken;

        // Add KYC status to user object for frontend context
        $userWithKycStatus = $user->toArray();
        $userWithKycStatus['kyc_status'] = $overallStatus;

        return response()->json([
            'success' => true,
            'data' => [
                'overall_status' => $overallStatus,
                'submissions' => $kycSubmissions,
                'total_submissions' => $kycSubmissions->count()
            ],
            'user' => $userWithKycStatus,
            // 'token' => $newToken
        ]);
    }

    /**
     * Refresh user token and return current user data
     * This helps maintain user context after verification processes
     */
    public function refresh(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        // Create new token (old one remains valid)
        $newToken = $user->createToken('auth_token')->plainTextToken;

        // Get current KYC status for user context
        $kycSubmissions = KycSubmission::where('user_id', $user->id)->get();
        $kycStatus = 'not_submitted';
        if ($kycSubmissions->isNotEmpty()) {
            $hasApproved = $kycSubmissions->contains('status', 'approved');
            $hasPending = $kycSubmissions->contains('status', 'pending');
            $hasRejected = $kycSubmissions->contains('status', 'rejected');

            if ($hasApproved) {
                $kycStatus = 'approved';
            } elseif ($hasPending) {
                $kycStatus = 'pending';
            } elseif ($hasRejected) {
                $kycStatus = 'rejected';
            }
        }

        // Add detailed KYC submissions data for frontend context
        $kycSubmissionsData = $kycSubmissions->map(function ($submission) {
            return [
                'id' => $submission->id,
                'document_type' => $submission->document_type,
                'document_file' => $submission->document_file,
                'document_url' => $submission->document_url,
                'status' => $submission->status,
                'meta' => $submission->meta,
                'submitted_at' => $submission->submitted_at?->toISOString(),
                'reviewed_at' => $submission->reviewed_at?->toISOString(),
                'rejection_reason' => $submission->rejection_reason,
                'reviewed_by' => $submission->reviewer?->name,
            ];
        });

        // Add KYC status to user object for frontend context
        $userWithKycStatus = $user->toArray();
        $userWithKycStatus['kyc_status'] = $kycStatus;

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed successfully',
            'user' => $userWithKycStatus,
            'token' => $newToken
        ]);
    }
}
