<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\AdminKycController;
use App\Http\Controllers\AdminPlanController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\InvestmentController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\WithdrawalController;
use App\Http\Controllers\AdminDepositController;
use App\Http\Controllers\AdminWithdrawalController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\AdminReferralController;
use App\Http\Controllers\PayoutController;
use App\Http\Controllers\AdminPayoutController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminProfileController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Protected authentication routes
Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    // Admin routes for user management
    Route::middleware('admin')->group(function () {
        Route::put('/users/{user}/suspend', [AuthController::class, 'suspendUser']);
        Route::put('/users/{user}/unsuspend', [AuthController::class, 'unsuspendUser']);
    });
});

// KYC routes for users
Route::middleware('auth:sanctum')->prefix('kyc')->group(function () {
    Route::post('/submit', [KycController::class, 'submit']);
    Route::get('/status', [KycController::class, 'status']);
});

// Admin KYC routes
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin/kyc')->group(function () {
    Route::get('/pending', [AdminKycController::class, 'pending']);
    Route::get('/{id}', [AdminKycController::class, 'show']);
    Route::get('/{id}/download', [AdminKycController::class, 'download'])->name('admin.kyc.download');
    Route::post('/{id}/approve', [AdminKycController::class, 'approve']);
    Route::post('/{id}/reject', [AdminKycController::class, 'reject']);
});

// Investment Plans & Transactions Routes

// Admin Plan Management Routes
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin/plans')->group(function () {
    Route::get('/', [AdminPlanController::class, 'index']);
    Route::post('/', [AdminPlanController::class, 'store']);
    Route::get('/{id}', [AdminPlanController::class, 'show']);
    Route::put('/{id}', [AdminPlanController::class, 'update']);
    Route::delete('/{id}', [AdminPlanController::class, 'destroy']);
});

// User Plan Routes
Route::middleware('auth:sanctum')->prefix('plans')->group(function () {
    Route::get('/', [PlanController::class, 'index']);
});

// Investment Routes
Route::middleware('auth:sanctum')->prefix('invest')->group(function () {
    Route::post('/', [InvestmentController::class, 'store']);
});
Route::middleware('auth:sanctum')->prefix('investments')->group(function () {
    Route::get('/', [InvestmentController::class, 'index']);
});

// Deposit Routes
Route::middleware('auth:sanctum')->prefix('deposit')->group(function () {
    Route::post('/', [DepositController::class, 'store']);
    Route::get('/history', [DepositController::class, 'history']);
});

// Withdrawal Routes
Route::middleware('auth:sanctum')->prefix('withdraw')->group(function () {
    Route::post('/', [WithdrawalController::class, 'store']);
    Route::get('/history', [WithdrawalController::class, 'history']);
});

// Admin Deposit Management Routes
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin/deposits')->group(function () {
    Route::get('/pending', [AdminDepositController::class, 'pending']);
    Route::post('/{id}/approve', [AdminDepositController::class, 'approve']);
    Route::post('/{id}/reject', [AdminDepositController::class, 'reject']);
});

// Admin Withdrawal Management Routes
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin/withdrawals')->group(function () {
    Route::get('/pending', [AdminWithdrawalController::class, 'pending']);
    Route::post('/{id}/approve', [AdminWithdrawalController::class, 'approve']);
    Route::post('/{id}/reject', [AdminWithdrawalController::class, 'reject']);
});

// Payout Routes for Users
Route::middleware('auth:sanctum')->prefix('payouts')->group(function () {
    Route::post('/request', [PayoutController::class, 'request']);
    Route::get('/history', [PayoutController::class, 'history']);
});

// Admin Payout Management Routes
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin/payouts')->group(function () {
    Route::get('/pending', [AdminPayoutController::class, 'pending']);
    Route::get('/approved', [AdminPayoutController::class, 'approved']);
    Route::get('/history', [AdminPayoutController::class, 'history']);
    Route::post('/{id}/approve', [AdminPayoutController::class, 'approve']);
    Route::post('/{id}/reject', [AdminPayoutController::class, 'reject']);
});

// Referral Routes for Users
Route::middleware('auth:sanctum')->prefix('referrals')->group(function () {
    Route::get('/', [ReferralController::class, 'index']);
    Route::get('/stats', [ReferralController::class, 'stats']);
});

// Admin Referral Management Routes
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin/referrals')->group(function () {
    Route::get('/pending', [AdminReferralController::class, 'pending']);
    Route::get('/confirmed', [AdminReferralController::class, 'confirmed']);
    Route::get('/history', [AdminReferralController::class, 'history']);
    Route::get('/{id}', [AdminReferralController::class, 'show']);
    Route::post('/{id}/approve', [AdminReferralController::class, 'approve']);
    Route::post('/{id}/reject', [AdminReferralController::class, 'reject']);
});

// Admin Dashboard Routes
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin/dashboard')->group(function () {
    Route::get('/summary', [AdminDashboardController::class, 'summary']);
    Route::get('/users', [AdminDashboardController::class, 'users']);
    Route::get('/finance', [AdminDashboardController::class, 'finance']);
});

// Admin Audit Logs Routes
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/audit-logs', [AdminDashboardController::class, 'auditLogs']);
});

// Admin Profile Management Routes
Route::middleware(['auth:sanctum', 'admin', 'admin.profile.rate.limit'])->prefix('admin/profile')->group(function () {
    Route::get('/', [AdminProfileController::class, 'profile']);
    Route::put('/update', [AdminProfileController::class, 'update']);
    Route::put('/email', [AdminProfileController::class, 'changeEmail']);
    Route::put('/password', [AdminProfileController::class, 'changePassword']);
    Route::post('/2fa/enable', [AdminProfileController::class, 'enable2FA']);
    Route::post('/2fa/verify', [AdminProfileController::class, 'verify2FA']);
    Route::post('/2fa/disable', [AdminProfileController::class, 'disable2FA']);
});

// Admin Activity Logs Routes
Route::middleware(['auth:sanctum', 'admin', 'admin.profile.rate.limit'])->prefix('admin/activity')->group(function () {
    Route::get('/logs', [AdminProfileController::class, 'activityLogs']);
});

// Email Verification Route
Route::middleware(['admin.profile.rate.limit'])->get('/admin/verify-email-change', [AdminProfileController::class, 'verifyEmailChange'])->name('admin.verify-email-change');

// User Dashboard Routes
Route::middleware('auth:sanctum')->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/overview', [DashboardController::class, 'overview'])->name('overview');
    Route::get('/recent-transactions', [DashboardController::class, 'recentTransactions'])->name('recent-transactions');
    Route::get('/investments-summary', [DashboardController::class, 'investmentsSummary'])->name('investments-summary');
    Route::get('/referrals-summary', [DashboardController::class, 'referralsSummary'])->name('referrals-summary');
    Route::get('/activity-log', [DashboardController::class, 'activityLog'])->name('activity-log');
});

// Legacy route (keeping for backward compatibility)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// User Profile Management Routes
Route::middleware('auth:sanctum')->prefix('user/profile')->group(function () {
    Route::get('/', [UserProfileController::class, 'profile']);
    Route::put('/update', [UserProfileController::class, 'update']);
    Route::put('/email', [UserProfileController::class, 'changeEmail']);
    Route::put('/password', [UserProfileController::class, 'changePassword']);
    Route::post('/2fa/enable', [UserProfileController::class, 'enable2FA']);
    Route::post('/2fa/verify', [UserProfileController::class, 'verify2FA']);
    Route::post('/2fa/disable', [UserProfileController::class, 'disable2FA']);
});

// User Activity Logs Routes
Route::middleware('auth:sanctum')->prefix('user/activity')->group(function () {
    Route::get('/logs', [UserProfileController::class, 'activityLogs']);
});

// Email Verification Route
Route::get('/user/verify-email-change', [UserProfileController::class, 'verifyEmailChange'])->name('user.verify-email-change');

// User Notification Routes
Route::middleware('auth:sanctum')->prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('/unread', [NotificationController::class, 'unread']);
    Route::get('/stats', [NotificationController::class, 'stats']);
    Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
});

// Admin Notification Routes
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin/notifications')->group(function () {
    Route::get('/', [AdminNotificationController::class, 'index']);
    Route::get('/unread', [AdminNotificationController::class, 'unread']);
    Route::get('/stats', [AdminNotificationController::class, 'stats']);
    Route::post('/{id}/read', [AdminNotificationController::class, 'markAsRead']);
    Route::post('/read-all', [AdminNotificationController::class, 'markAllAsRead']);
    Route::post('/system', [AdminNotificationController::class, 'createSystemNotification']);
});
