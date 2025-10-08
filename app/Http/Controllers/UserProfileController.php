<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuditLog;
use App\Services\NotificationService;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\ChangeEmailRequest;
use App\Http\Requests\ChangePasswordRequest;
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

class UserProfileController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Get user profile
     * GET /api/user/profile
     */
    public function profile(Request $request)
    {
        $user = $request->user();

        // Get activity summary
        $activitySummary = [
            'last_login' => $user->last_login_at?->format('Y-m-d H:i:s'),
            'last_login_ip' => $user->last_login_ip,
            'total_logins' => AuditLog::where('user_id', $user->id)
                ->where('action_type', 'login')
                ->count(),
            'two_factor_enabled' => $user->two_factor_enabled,
        ];

        return ApiResponse::success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'display_name' => $user->display_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'telegram' => $user->telegram,
                'dob' => $user->dob?->format('Y-m-d'),
                'address' => $user->address,
                'avatar' => $user->avatar,
                'status' => $user->status,
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $user->updated_at->format('Y-m-d H:i:s'),
            ],
            'activity_summary' => $activitySummary,
        ], 'Profile retrieved successfully');
    }

    /**
     * Update personal information
     * PUT /api/user/profile/update
     */
    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();

        $user->update($request->only([
            'display_name', 'phone', 'telegram', 'dob', 'address'
        ]));

        // Log the activity
        AuditLog::create([
            'admin_id' => null,
            'user_id' => $user->id,
            'action_type' => 'profile_updated',
            'target_id' => $user->id,
            'target_type' => 'User',
            'details' => 'Updated personal information',
            'ip_address' => $request->ip(),
        ]);

        return ApiResponse::success([
            'user' => $user,
        ], 'Profile updated successfully');
    }

    /**
     * Change email address
     * PUT /api/user/profile/email
     */
    public function changeEmail(ChangeEmailRequest $request)
    {
        $user = $request->user();

        // Verify current email and password
        if ($request->current_email !== $user->email) {
            return ApiResponse::error('Current email does not match your account', 400);
        }

        if (!Hash::check($request->password, $user->password)) {
            return ApiResponse::error('Current password is incorrect', 400);
        }

        // Generate verification token
        $verificationToken = Str::random(64);
        $user->update([
            'new_email' => $request->new_email,
            'email_verification_token' => $verificationToken,
            'email_verification_expires' => Carbon::now()->addHours(24),
        ]);

        // Send verification email
        try {
            Mail::raw(
                "Please click the following link to verify your new email address: " .
                url('/api/user/verify-email-change?token=' . $verificationToken),
                function ($message) use ($request) {
                    $message->to($request->new_email)
                        ->subject('Verify Your New Email Address');
                }
            );

            // Log the activity
            AuditLog::create([
                'admin_id' => null,
                'user_id' => $user->id,
                'action_type' => 'email_change_requested',
                'target_id' => $user->id,
                'target_type' => 'User',
                'details' => 'Requested email change to: ' . $request->new_email,
                'ip_address' => $request->ip(),
            ]);

            return ApiResponse::success(null, 'Verification email sent to your new email address. Please check your inbox and click the verification link.');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to send verification email. Please try again.');
        }
    }

    /**
     * Verify email change
     * GET /api/user/verify-email-change
     */
    public function verifyEmailChange(Request $request)
    {
        $token = $request->query('token');

        if (!$token) {
            return ApiResponse::error('Invalid verification token', 400);
        }

        $user = User::where('email_verification_token', $token)
            ->where('email_verification_expires', '>', Carbon::now())
            ->first();

        if (!$user) {
            return ApiResponse::error('Invalid or expired verification token', 400);
        }

        // Update email
        $oldEmail = $user->email;
        $user->update([
            'email' => $user->new_email,
            'new_email' => null,
            'email_verification_token' => null,
            'email_verification_expires' => null,
        ]);

        // Send confirmation email
        try {
            Mail::raw(
                "Your email address has been successfully changed from {$oldEmail} to {$user->email}",
                function ($message) use ($user) {
                    $message->to($user->email)
                        ->subject('Email Address Changed Successfully');
                }
            );

            // Log the activity
            AuditLog::create([
                'admin_id' => null,
                'user_id' => $user->id,
                'action_type' => 'email_changed',
                'target_id' => $user->id,
                'target_type' => 'User',
                'details' => "Email changed from {$oldEmail} to {$user->email}",
                'ip_address' => $request->ip(),
            ]);

            return ApiResponse::success(null, 'Email address changed successfully');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Email changed but failed to send confirmation email');
        }
    }

    /**
     * Change password
     * PUT /api/user/profile/password
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return ApiResponse::error('Current password is incorrect', 400);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        // Send notification
        $this->notificationService->createUserNotification(
            $user->id,
            'Password Changed',
            'Your password has been changed successfully',
            'security',
            ['action' => 'password_change']
        );

        // Log the activity
        AuditLog::create([
            'admin_id' => null,
            'user_id' => $user->id,
            'action_type' => 'password_changed',
            'target_id' => $user->id,
            'target_type' => 'User',
            'details' => 'Password changed successfully',
            'ip_address' => $request->ip(),
        ]);

        return ApiResponse::success(null, 'Password changed successfully');
    }

    /**
     * Enable 2FA
     * POST /api/user/profile/2fa/enable
     */
    public function enable2FA(Request $request)
    {
        $user = $request->user();

        if ($user->two_factor_enabled) {
            return ApiResponse::error('2FA is already enabled', 400);
        }

        $google2fa = new Google2FA();

        // Generate secret key
        $secret = $google2fa->generateSecretKey();

        // Generate QR code URL
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name') . ' - ' . $user->name,
            $user->email,
            $secret
        );

        // Store secret temporarily
        $user->update([
            'two_factor_secret' => $secret,
        ]);

        return ApiResponse::success([
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
        ], '2FA setup initiated. Please verify the code to complete setup.');
    }

    /**
     * Verify and enable 2FA
     * POST /api/user/profile/2fa/verify
     */
    public function verify2FA(Enable2FARequest $request)
    {
        $user = $request->user();

        if (!$user->two_factor_secret) {
            return ApiResponse::error('2FA setup not initiated', 400);
        }

        $google2fa = new Google2FA();

        $valid = $google2fa->verifyKey(
            $user->two_factor_secret,
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
        $user->update([
            'two_factor_enabled' => true,
            'two_factor_recovery_codes' => $recoveryCodes,
        ]);

        // Send notification
        $this->notificationService->createUserNotification(
            $user->id,
            '2FA Enabled',
            'Two-factor authentication has been enabled for your account',
            'security',
            ['action' => '2fa_enabled']
        );

        // Log the activity
        AuditLog::create([
            'admin_id' => null,
            'user_id' => $user->id,
            'action_type' => '2fa_enabled',
            'target_id' => $user->id,
            'target_type' => 'User',
            'details' => 'Two-factor authentication enabled',
            'ip_address' => $request->ip(),
        ]);

        return ApiResponse::success([
            'recovery_codes' => $recoveryCodes,
        ], '2FA enabled successfully');
    }

    /**
     * Disable 2FA
     * POST /api/user/profile/2fa/disable
     */
    public function disable2FA(Disable2FARequest $request)
    {
        $user = $request->user();

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return ApiResponse::error('Password is incorrect', 400);
        }

        // Verify 2FA code if enabled
        if ($user->two_factor_enabled && $user->two_factor_secret && $request->filled('verification_code')) {
            $google2fa = new Google2FA();

            $valid = $google2fa->verifyKey(
                $user->two_factor_secret,
                $request->verification_code
            );

            if (!$valid) {
                return ApiResponse::error('Invalid 2FA verification code', 400);
            }
        }

        // Disable 2FA
        $user->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ]);

        // Send notification
        $this->notificationService->createUserNotification(
            $user->id,
            '2FA Disabled',
            'Two-factor authentication has been disabled for your account',
            'security',
            ['action' => '2fa_disabled']
        );

        // Log the activity
        AuditLog::create([
            'admin_id' => null,
            'user_id' => $user->id,
            'action_type' => '2fa_disabled',
            'target_id' => $user->id,
            'target_type' => 'User',
            'details' => 'Two-factor authentication disabled',
            'ip_address' => $request->ip(),
        ]);

        return ApiResponse::success(null, '2FA disabled successfully');
    }

    /**
     * Get activity logs
     * GET /api/user/activity/logs
     */
    public function activityLogs(Request $request)
    {
        $user = $request->user();

        $query = AuditLog::where('user_id', $user->id)
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

        return ApiResponse::paginated($logs, 'Activity logs retrieved successfully');
    }
}
