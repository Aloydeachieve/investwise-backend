<?php

namespace App\Http\Controllers;

use App\Models\KycSubmission;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\ApproveKycRequest;
use App\Http\Requests\RejectKycRequest;

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
                'status' => $submission->status, // 👈 add this
                'meta' => $submission->meta,
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

    public function approved(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        $approvedSubmissions = KycSubmission::with(['user', 'reviewer'])
            ->where('status', 'approved')
            ->orderBy('submitted_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $formattedSubmissions = $approvedSubmissions->getCollection()->map(function ($submission) {
            return [
                'id' => $submission->id,
                'user' => [
                    'id' => $submission->user->id,
                    'name' => $submission->user->name,
                    'email' => $submission->user->email,
                ],
                'document_type' => $submission->document_type,
                'status' => $submission->status, // 👈 add this
                'meta' => $submission->meta,
                'submitted_at' => $submission->submitted_at->toISOString(),
                'document_url' => route('admin.kyc.download', $submission->id),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'submissions' => $formattedSubmissions,
                'pagination' => [
                    'current_page' => $approvedSubmissions->currentPage(),
                    'last_page' => $approvedSubmissions->lastPage(),
                    'per_page' => $approvedSubmissions->perPage(),
                    'total' => $approvedSubmissions->total(),
                ]
            ]
        ]);
    }

    public function all(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        $allSubmissions = KycSubmission::with(['user', 'reviewer'])
            ->orderBy('submitted_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $formattedSubmissions = $allSubmissions->getCollection()->map(function ($submission) {
            return [
                'id' => $submission->id,
                'user' => [
                    'id' => $submission->user->id,
                    'name' => $submission->user->name,
                    'email' => $submission->user->email,
                ],
                'document_type' => $submission->document_type,
                'status' => $submission->status,
                'meta' => $submission->meta,
                'submitted_at' => $submission->submitted_at->toISOString(),
                'document_url' => route('admin.kyc.download', $submission->id),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'submissions' => $formattedSubmissions,
                'pagination' => [
                    'current_page' => $allSubmissions->currentPage(),
                    'last_page' => $allSubmissions->lastPage(),
                    'per_page' => $allSubmissions->perPage(),
                    'total' => $allSubmissions->total(),
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
                    'phone' => $submission->user->phone ?? null,
                    'address' => $submission->user->address ?? null,
                ],
                'document_type' => $submission->document_type,
                'meta' => $submission->meta,
                'status' => $submission->status,
                'submitted_at' => $submission->submitted_at->toISOString(),
                'reviewed_at' => $submission->reviewed_at?->toISOString(),
                'rejection_reason' => $submission->rejection_reason,
                'reviewed_by' => $submission->reviewer?->name,

                // 👇 Add both metadata URL and direct preview URL
                'document_url' => route('admin.kyc.download', $submission->id),
                // 'document_preview_url' => route('admin.kyc.download', ['id' => $submission->id, 'preview' => 1]),
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

    public function download(Request $request, $id)
    {
        $submission = KycSubmission::findOrFail($id);

        if (!$submission->document_file || !Storage::disk('private')->exists($submission->document_file)) {
            return response()->json([
                'success' => false,
                'message' => 'Document file not found'
            ], 404);
        }

        // ✅ Allow public preview (raw file stream)
        if ($request->has('preview')) {
            $mimeType = Storage::disk('private')->mimeType($submission->document_file);
            $stream = Storage::disk('private')->readStream($submission->document_file);

            return response()->stream(function () use ($stream) {
                fpassthru($stream);
            }, 200, [
                "Content-Type" => $mimeType,
                "Content-Disposition" => "inline; filename=\"" . basename($submission->document_file) . "\"",
                "Access-Control-Allow-Origin" => "*", // ⚠️ for dev; in prod use your domain
                "Access-Control-Allow-Headers" => "Authorization, Content-Type",
            ]);
        }

        // ✅ Require admin auth for metadata
        if (!$request->user() || !$request->user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $submission->id,
                'document_type' => $submission->document_type,
                'filename' => basename($submission->document_file),
                'file_path' => $submission->document_file,
                'note' => 'Use ?preview=1 to view document'
            ]
        ]);
    }
}
