<?php

namespace App\Http\Controllers;

use App\Models\KycSubmission;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class AdminKycController extends Controller
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

        $pendingSubmissions = KycSubmission::with(['user', 'reviewer'])
            ->where('status', 'pending')
            ->orderBy('submitted_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $formattedSubmissions = $pendingSubmissions->getCollection()->map(function ($submission) {
            return [
                'id' => $submission->id,
                'user' => [
                    'id' => $submission->user->id,
                    'name' => $submission->user->name,
                    'email' => $submission->user->email,
                ],
                'document_type' => $submission->document_type,
                'submitted_at' => $submission->submitted_at->toISOString(),
                'document_url' => route('admin.kyc.download', $submission->id),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'submissions' => $formattedSubmissions,
                'pagination' => [
                    'current_page' => $pendingSubmissions->currentPage(),
                    'last_page' => $pendingSubmissions->lastPage(),
                    'per_page' => $pendingSubmissions->perPage(),
                    'total' => $pendingSubmissions->total(),
                ]
            ]
        ]);
    }

    public function show($id): JsonResponse
    {
        $submission = KycSubmission::with(['user', 'reviewer'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $submission->id,
                'user' => [
                    'id' => $submission->user->id,
                    'name' => $submission->user->name,
                    'email' => $submission->user->email,
                ],
                'document_type' => $submission->document_type,
                'status' => $submission->status,
                'submitted_at' => $submission->submitted_at->toISOString(),
                'reviewed_at' => $submission->reviewed_at?->toISOString(),
                'rejection_reason' => $submission->rejection_reason,
                'reviewed_by' => $submission->reviewer?->name,
                'document_url' => route('admin.kyc.download', $submission->id),
            ]
        ]);
    }

    public function approve(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $submission = KycSubmission::findOrFail($id);

        if ($submission->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending submissions can be approved'
            ], 400);
        }

        $submission->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => Auth::id(),
        ]);

        // Create notification for user
        $this->notificationService->notifyKycEvent(
            $submission->user,
            'approved',
            [
                'submission_id' => $submission->id,
                'document_type' => $submission->document_type
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'KYC submission approved successfully',
            'data' => [
                'id' => $submission->id,
                'status' => $submission->status,
                'reviewed_at' => $submission->reviewed_at->toISOString(),
                'reviewed_by' => Auth::user()->name,
            ]
        ]);
    }

    public function reject(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|max:1000',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $submission = KycSubmission::findOrFail($id);

        if ($submission->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending submissions can be rejected'
            ], 400);
        }

        $submission->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'reviewed_at' => now(),
            'reviewed_by' => Auth::id(),
        ]);

        // Create notification for user
        $this->notificationService->notifyKycEvent(
            $submission->user,
            'rejected',
            [
                'submission_id' => $submission->id,
                'document_type' => $submission->document_type,
                'rejection_reason' => $request->rejection_reason
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'KYC submission rejected successfully',
            'data' => [
                'id' => $submission->id,
                'status' => $submission->status,
                'rejection_reason' => $submission->rejection_reason,
                'reviewed_at' => $submission->reviewed_at->toISOString(),
                'reviewed_by' => Auth::user()->name,
            ]
        ]);
    }

    public function download($id): JsonResponse
    {
        $submission = KycSubmission::findOrFail($id);

        // Check if file exists
        if (!Storage::disk('private')->exists($submission->document_file)) {
            return response()->json([
                'success' => false,
                'message' => 'Document file not found'
            ], 404);
        }

        // For private files, we'll return the file path and let the frontend handle the download
        // In a production environment, you might want to use Laravel's signed URLs or a different approach
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $submission->id,
                'document_type' => $submission->document_type,
                'filename' => basename($submission->document_file),
                'file_path' => $submission->document_file,
                'note' => 'For security reasons, documents must be accessed through the admin panel interface',
            ]
        ]);
    }
}
