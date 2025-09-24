<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminNotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->middleware('auth:sanctum');
        $this->middleware('admin');
        $this->notificationService = $notificationService;
    }

    /**
     * Get all admin notifications (paginated)
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $type = $request->get('type');

        $query = Notification::forAdmins()
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc');

        if ($type) {
            $query->where('type', $type);
        }

        $notifications = $query->paginate($perPage);

        // Add unread count to response
        $unreadCount = $this->notificationService->getAdminUnreadCount();

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Get unread admin notifications
     */
    public function unread(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $type = $request->get('type');

        $query = Notification::forAdmins()
            ->unread()
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc');

        if ($type) {
            $query->where('type', $type);
        }

        $notifications = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'unread_count' => $this->notificationService->getAdminUnreadCount()
        ]);
    }

    /**
     * Mark single admin notification as read
     */
    public function markAsRead(int $id): JsonResponse
    {
        $success = $this->notificationService->markAsReadForAdmin($id);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read',
                'unread_count' => $this->notificationService->getAdminUnreadCount()
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Notification not found or access denied'
        ], 404);
    }

    /**
     * Mark all admin notifications as read
     */
    public function markAllAsRead(): JsonResponse
    {
        $count = $this->notificationService->markAllAsReadForAdmin();

        return response()->json([
            'success' => true,
            'message' => "Marked {$count} notifications as read",
            'unread_count' => 0
        ]);
    }

    /**
     * Get admin notification statistics
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total' => Notification::forAdmins()->count(),
            'unread' => $this->notificationService->getAdminUnreadCount(),
            'read' => Notification::forAdmins()->read()->count(),
            'by_type' => [
                'deposit' => Notification::forAdmins()->ofType('deposit')->count(),
                'withdrawal' => Notification::forAdmins()->ofType('withdrawal')->count(),
                'investment' => Notification::forAdmins()->ofType('investment')->count(),
                'referral' => Notification::forAdmins()->ofType('referral')->count(),
                'payout' => Notification::forAdmins()->ofType('payout')->count(),
                'kyc' => Notification::forAdmins()->ofType('kyc')->count(),
                'system' => Notification::forAdmins()->ofType('system')->count(),
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Create a system notification for all admins
     */
    public function createSystemNotification(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|string|in:deposit,withdrawal,investment,referral,payout,kyc,system',
            'meta' => 'nullable|array'
        ]);

        $notification = $this->notificationService->createAdminNotification(
            $request->title,
            $request->message,
            $request->type,
            $request->meta ?? []
        );

        return response()->json([
            'success' => true,
            'message' => 'System notification created successfully',
            'data' => $notification->load('user:id,name,email')
        ], 201);
    }
}
