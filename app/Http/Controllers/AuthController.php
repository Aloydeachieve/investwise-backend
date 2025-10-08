<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Helpers\ApiResponse;
use App\Models\KycSubmission;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;


class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'user',
            'status' => 'active',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        // Attach KYC status
        $userData = $user->toArray();
        $userData['kyc_status'] = KycSubmission::where('user_id', $user->id)
            ->latest('id')
            ->value('status') ?? 'not_submitted';

        return ApiResponse::success([
            'user' => $userData,
            'token' => $token,
        ], 'User registered successfully', 201);
    }

    /**
     * Login user
     */
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check if user is active
        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['Your account is not active. Please contact support.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Attach KYC status
        $userData = $user->toArray();
        $userData['kyc_status'] = KycSubmission::where('user_id', $user->id)
            ->latest('id')
            ->value('status') ?? 'not_submitted';

        return ApiResponse::success([
            'user' => $userData,
            'token' => $token,
        ], 'Login successful');
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, 'Logged out successfully');
    }

    /**
     * Get authenticated user profile
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return ApiResponse::success([
            'user' => $user,
            'token' => $token,
        ], 'Profile retrieved successfully');
    }

    /**
     * Refresh user token
     * This helps maintain user context after verification processes
     */
    public function refreshToken(Request $request)
    {
        $user = $request->user();
        $newToken = $user->createToken('auth_token')->plainTextToken;

        return ApiResponse::success([
            'user' => $user,
            'token' => $newToken,
        ], 'Token refreshed successfully');
    }

    /**
     * Update user profile
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $user->update($request->only(['name', 'email']));

        if ($request->filled('password')) {
            $user->update([
                'password' => Hash::make($request->password),
            ]);
        }

        return ApiResponse::success([
            'user' => $user,
        ], 'Profile updated successfully');
    }

    /**
     * Send password reset link
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return ApiResponse::success(null, 'Password reset link sent to your email address.');
        } else {
            throw ValidationException::withMessages([
                'email' => ['Unable to send password reset link.'],
            ]);
        }
    }

    /**
     * Reset password
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(str()->random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return ApiResponse::success(null, 'Password reset successfully.');
        } else {
            throw ValidationException::withMessages([
                'email' => ['Unable to reset password.'],
            ]);
        }
    }

    // 🔹 List Users
    public function activeUsers()
    {
        $users = User::where('status', 'active')->get();
        return ApiResponse::success($users, 'Active users retrieved successfully');
    }

    public function inactiveUsers()
    {
        $users = User::where('status', 'inactive')->get();
        return ApiResponse::success($users, 'Inactive users retrieved successfully');
    }

    public function suspendedUsers()
    {
        $users = User::where('status', 'suspended')->get();
        return ApiResponse::success($users, 'Suspended users retrieved successfully');
    }

    public function allUsers()
    {
        $users = User::all();
        return ApiResponse::success($users, 'All users retrieved successfully');
    }

    // 🔹 Create new user
    public function createUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'nullable|string|in:user,admin'
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'] ?? 'user',
            'status' => 'active'
        ]);

        return ApiResponse::success($user, 'User created successfully', 201);
    }

    // 🔹 Delete user
    public function deleteUser(User $user)
    {
        $user->delete();
        return ApiResponse::success(null, 'User deleted successfully');
    }

    // 🔹 Send email to user
    public function sendEmail(Request $request, User $user)
    {
        try {
            // Check if user is authenticated and is admin
            if (!$request->user() || $request->user()->role !== 'admin') {
                return ApiResponse::error('Unauthorized', 403);
            }

            // Validate required parameters
            $validated = $request->validate([
                'subject' => 'required|string|max:255',
                'message' => 'required|string'
            ]);

            // Prepare email data
            $emailData = [
                'subject' => $validated['subject'],
                'message' => $validated['message'],
                'recipient_name' => $user->name,
                'recipient_email' => $user->email
            ];

            // Send email using CustomNotificationMail
            Mail::to($user->email)->send(new \App\Mail\CustomNotificationMail($emailData));

            // Log successful email sending
            Log::info("Email sent successfully to {$user->email}", [
                'subject' => $validated['subject'],
                'recipient_id' => $user->id
            ]);

            return ApiResponse::success([
                'recipient' => $user->email,
                'subject' => $validated['subject']
            ], 'Email sent successfully');
        } catch (\Exception $e) {
            Log::error("Email error: " . $e->getMessage(), [
                'recipient' => $user->email ?? 'unknown',
                'error' => $e->getTraceAsString()
            ]);
            return ApiResponse::error('Failed to send email: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Admin: Suspend user
     */
    public function suspendUser(Request $request, User $user)
    {
        $request->user()->can('admin'); // Check if user is admin

        $user->update(['status' => 'suspended']);

        return ApiResponse::success([
            'user' => $user,
        ], 'User suspended successfully');
    }

    /**
     * Admin: Unsuspend user
     */
    public function unsuspendUser(Request $request, User $user)
    {
        $request->user()->can('admin'); // Check if user is admin

        $user->update(['status' => 'active']);

        return ApiResponse::success([
            'user' => $user,
        ], 'User unsuspended successfully');
    }

    // 🔹 Get User Details with Relationships
    public function getUserDetails($id)
    {
        $user = User::with([
            'transactions',
            'investments',
            'referrals',
            'activities',
        ])->findOrFail($id);

        return response()->json([
            'data' => $user
        ]);
    }
}
