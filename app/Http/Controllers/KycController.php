<?php

namespace App\Http\Controllers;

use App\Models\KycSubmission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class KycController extends Controller
{
    public function submit(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'documents' => 'required|array|min:1|max:5',
            'documents.*.type' => 'required|in:passport,driver_license,national_id,proof_of_address,utility_bill,bank_statement',
            'documents.*.file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

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

        foreach ($request->file('documents') as $document) {
            $type = $document['type'];
            $file = $document['file'];

            // Generate unique filename
            $filename = time() . '_' . $user->id . '_' . $type . '.' . $file->getClientOriginalExtension();

            // Store file in private storage
            $path = $file->storeAs('kyc_documents', $filename, 'private');

            // Create KYC submission record
            $kycSubmission = KycSubmission::create([
                'user_id' => $user->id,
                'document_type' => $type,
                'document_file' => $path,
                'status' => 'pending',
                'submitted_at' => now(),
            ]);

            $submittedDocuments[] = [
                'id' => $kycSubmission->id,
                'document_type' => $kycSubmission->document_type,
                'status' => $kycSubmission->status,
                'submitted_at' => $kycSubmission->submitted_at->toISOString(),
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'KYC documents submitted successfully',
            'data' => [
                'documents' => $submittedDocuments,
                'total_submitted' => count($submittedDocuments)
            ]
        ], 201);
    }

    public function status(): JsonResponse
    {
        $user = Auth::user();

        $kycSubmissions = KycSubmission::where('user_id', $user->id)
            ->orderBy('submitted_at', 'desc')
            ->get()
            ->map(function ($submission) {
                return [
                    'id' => $submission->id,
                    'document_type' => $submission->document_type,
                    'status' => $submission->status,
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

        return response()->json([
            'success' => true,
            'data' => [
                'overall_status' => $overallStatus,
                'submissions' => $kycSubmissions,
                'total_submissions' => $kycSubmissions->count()
            ]
        ]);
    }
}
