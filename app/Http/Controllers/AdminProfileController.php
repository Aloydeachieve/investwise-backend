<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuditLog;
use App\Services\NotificationService;
use App\Http\Requests\UpdateAdminProfileRequest;
use App\Http\Requests\ChangeAdminEmailRequest;
use App\Http\Requests\ChangeAdminPasswordRequest;
use App\Http\Requests\Enable2FARequest;
use App\Http\Requests\Disable2FARequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use PragmaRX\Google2FA\Google2FA;
use App\Helpers\ApiResponse;

class AdminProfileController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
        $this->middleware('auth:sanctum');
        $this->middleware('admin');
    }

    /**
     * Get admin profile
     * GET /api/admin/profile
     */
    public function profile(Request $request)
    {
        $admin = $request->user();

        // Get activity summary
        $activitySummary = [
            'last_login' => $admin->last_login_at?->format('Y-m-d H:i:s'),
            'last_login_ip' => $admin->last_login_ip,
            'total_logins' => AuditLog::where('admin_id', $admin->id)
                ->where('action_type', 'login')
                ->count(),
            'two_factor_enabled' => $admin->two_factor_enabled,
        ];

        return ApiResponse::success([
            'admin' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'display_name' => $admin->display_name,
                'email' => $admin->email,
                'phone' => $admin->phone,
                'telegram' => $admin->telegram,
                'dob' => $admin->dob?->format('Y-m-d'),
                'address' => $admin->address,
                'avatar' => $admin->avatar,
                'role' => $admin->role,
                'status' => $admin->status,
                'created_at' => $admin->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $admin->updated_at->format('Y-m-d H:i:s'),
            ],
            'activity_summary' => $activitySummary,
        ], 'Admin profile retrieved successfully');
    }

    /**
     * Update personal information
     * PUT /api/admin/profile/update
     */
    public function update(UpdateAdminProfileRequest $request)
    {
        $admin = $request->user();

        $admin->update($request->only([
            'display_name', 'phone', 'telegram', 'dob', 'address'
        ]));

        // Log the activity
        AuditLog::log(
            $admin->id,
            'profile_updated',
            $admin->id,
            'User',
            'Updated personal information',
            $request->ip()
        );

        return ApiResponse::success([
            'admin' => $admin,
        ], 'Admin profile updated successfully');
    }

    /**
     * Change email address
     * PUT /api/admin/profile/email
     */
    public function changeEmail(ChangeAdminEmailRequest $request)
    {
        $admin = $request->user();

        // Verify current email and password
        if ($request->current_email !== $admin->email) {
            return ApiResponse::error('Current email does not match your account', 400);
        }

        if (!Hash::check($request->password, $admin->password)) {
            return ApiResponse::error('Current password is incorrect', 400);
        }

        // Generate verification token
        $verificationToken = Str::random(64);
        $admin->update([
            'new_email' => $request->new_email,
            'email_verification_token' => $verificationToken,
            'email_verification_expires' => Carbon::now()->addHours(24),
        ]);

        // Send verification email
        try {
            Mail::raw(
                "Please click the following link to verify your new email address: " .
                url('/api/admin/verify-email-change?token=' . $verificationToken),
                function ($message) use ($request) {
                    $message->to($request->new_email)
                        ->subject('Verify Your New Email Address');
                }
            );

            // Log the activity
            AuditLog::log(
                $admin->id,
                'email_change_requested',
                $admin->id,
                'User',
                'Requested email change to: ' . $request->new_email,
                $request->ip()
            );

            return ApiResponse::success(null, 'Verification email sent to your new email address. Please check your inbox and click the verification link.');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to send verification email. Please try again.');
        }
    }

    /**
     * Verify email change
     * GET /api/admin/verify-email-change
     */
    public function verifyEmailChange(Request $request)
    {
        $token = $request->query('token');

        if (!$token) {
            return ApiResponse::error('Invalid verification token', 400);
        }

        $admin = User::where('email_verification_token', $token)
            ->where('email_verification_expires', '>', Carbon::now())
            ->first();

        if (!$admin) {
            return ApiResponse::error('Invalid or expired verification token', 400);
        }

        // Update email
        $oldEmail = $admin->email;
        $admin->update([
            'email' => $admin->new_email,
            'new_email' => null,
            'email_verification_token' => null,
            'email_verification_expires' => null,
        ]);

        // Send confirmation email
        try {
            Mail::raw(
                "Your email address has been successfully changed from {$oldEmail} to {$admin->email}",
                function ($message) use ($admin) {
                    $message->to($admin->email)
                        ->subject('Email Address Changed Successfully');
                }
            );

            // Log the activity
            AuditLog::log(
                $admin->id,
                'email_changed',
                $admin->id,
                'User',
                "Email changed from {$oldEmail} to {$admin->email}",
                $request->ip()
            );

            return ApiResponse::success(null, 'Email address changed successfully');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Email changed but failed to send confirmation email');
        }
    }

    /**
     * Change password
     * PUT /api/admin/profile/password
     */
    public function changePassword(ChangeAdminPasswordRequest $request)
    {
        $admin = $request->user();

        // Verify current password
        if (!Hash::check($request->current_password, $admin->password)) {
            return ApiResponse::error('Current password is incorrect', 400);
        }

        // Update password
        $admin->update([
            'password' => Hash::make($request->new_password),
        ]);

        // Send notification
        $this->notificationService->createAdminNotification(
            'Password Changed',
            'Your password has been changed successfully',
            'security',
            ['action' => 'password_change']
        );

        // Log the activity
        AuditLog::log(
            $admin->id,
            'password_changed',
            $admin->id,
            'User',
            'Password changed successfully',
            $request->ip()
        );

        return ApiResponse::success(null, 'Password changed successfully');
    }

    /**
     * Enable 2FA
     * POST /api/admin/profile/2fa/enable
     */
    public function enable2FA(Request $request)
    {
        $admin = $request->user();

        if ($admin->two_factor_enabled) {
            return ApiResponse::error('2FA is already enabled', 400);
        }

        $google2fa = new Google2FA();

        // Generate secret key
        $secret = $google2fa->generateSecretKey();

        // Generate QR code URL
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name') . ' - ' . $admin->name,
            $admin->email,
            $secret
        );

        // Store secret temporarily
        $admin->update([
            'two_factor_secret' => $secret,
        ]);

        return ApiResponse::success([
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
        ], '2FA setup initiated. Please verify the code to complete setup.');
    }

    /**
     * Verify and enable 2FA
     * POST /api/admin/profile/2fa/verify
     */
    public function verify2FA(Enable2FARequest $request)
    {
        $admin = $request->user();

        if (!$admin->two_factor_secret) {
            return ApiResponse::error('2FA setup not initiated', 400);
        }

        $google2fa = new Google2FA();

        $valid = $google2fa->verifyKey(
            $admin->two_factor_secret,
            $request->verification_code
        );

        if (!$valid) {
            return ApiResponse::error('Invalid verification code', 400);
        }

        // Generate recovery codes
        $recoveryCodes = [];
        for ($i = 0; $i < 10; $i++) {
            $recoveryCodes[] = Str::random(8);
        }

        // Enable 2FA
        $admin->update([
            'two_factor_enabled' => true,
            'two_factor_recovery_codes' => $recoveryCodes,
        ]);

        // Send notification
        $this->notificationService->createAdminNotification(
            '2FA Enabled',
            'Two-factor authentication has been enabled for your account',
            'security',
            ['action' => '2fa_enabled']
        );

        // Log the activity
        AuditLog::log(
            $admin->id,
            '2fa_enabled',
            $admin->id,
            'User',
            'Two-factor authentication enabled',
            $request->ip()
        );

        return ApiResponse::success([
            'recovery_codes' => $recoveryCodes,
        ], '2FA enabled successfully');
    }

    /**
     * Disable 2FA
     * POST /api/admin/profile/2fa/disable
     */
    public function disable2FA(Disable2FARequest $request)
    {
        $admin = $request->user();

        // Verify password
        if (!Hash::check($request->password, $admin->password)) {
            return ApiResponse::error('Password is incorrect', 400);
        }

        // Verify 2FA code if enabled
        if ($admin->two_factor_enabled && $admin->two_factor_secret && $request->filled('verification_code')) {
            $google2fa = new Google2FA();

            $valid = $google2fa->verifyKey(
                $admin->two_factor_secret,
                $request->verification_code
            );

            if (!$valid) {
                return ApiResponse::error('Invalid 2FA verification code', 400);
            }
        }

        // Disable 2FA
        $admin->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ]);

        // Send notification
        $this->notificationService->createAdminNotification(
            '2FA Disabled',
            'Two-factor authentication has been disabled for your account',
            'security',
            ['action' => '2fa_disabled']
        );

        // Log the activity
        AuditLog::log(
            $admin->id,
            '2fa_disabled',
            $admin->id,
            'User',
            'Two-factor authentication disabled',
            $request->ip()
        );

        return ApiResponse::success(null, '2FA disabled successfully');
    }

    /**
     * Get activity logs
     * GET /api/admin/activity/logs
     */
    public function activityLogs(Request $request)
    {
        $admin = $request->user();

        $query = AuditLog::where('admin_id', $admin->id)
            ->with('admin:id,name,email')
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('action_type')) {
            $query->where('action_type', $request->action_type);
        }

        if ($request->filled('status')) {
            // You can add status filtering logic here if needed
        }

        $logs = $query->paginate($request->get('per_page', 15));

        return ApiResponse::paginated($logs, 'Admin activity logs retrieved successfully');
    }
}
