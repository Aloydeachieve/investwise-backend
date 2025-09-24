<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->middleware('auth:sanctum');
        $this->notificationService = $notificationService;
    }

    /**
     * Get all user notifications (paginated)
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $perPage = $request->get('per_page', 15);
        $type = $request->get('type');

        $query = Notification::where('user_id', $user->id)
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc');

        if ($type) {
            $query->where('type', $type);
        }

        $notifications = $query->paginate($perPage);

        // Add unread count to response
        $unreadCount = $this->notificationService->getUnreadCount($user);

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Get unread notifications
     */
    public function unread(Request $request): JsonResponse
    {
        $user = Auth::user();
        $perPage = $request->get('per_page', 15);
        $type = $request->get('type');

        $query = Notification::where('user_id', $user->id)
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
            'unread_count' => $this->notificationService->getUnreadCount($user)
        ]);
    }

    /**
     * Mark single notification as read
     */
    public function markAsRead(int $id): JsonResponse
    {
        $user = Auth::user();

        $success = $this->notificationService->markAsRead($id, $user);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read',
                'unread_count' => $this->notificationService->getUnreadCount($user)
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Notification not found or access denied'
        ], 404);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): JsonResponse
    {
        $user = Auth::user();

        $count = $this->notificationService->markAllAsRead($user);

        return response()->json([
            'success' => true,
            'message' => "Marked {$count} notifications as read",
            'unread_count' => 0
        ]);
    }

    /**
     * Get notification statistics
     */
    public function stats(): JsonResponse
    {
        $user = Auth::user();

        $stats = [
            'total' => Notification::where('user_id', $user->id)->count(),
            'unread' => $this->notificationService->getUnreadCount($user),
            'read' => Notification::where('user_id', $user->id)->read()->count(),
            'by_type' => [
                'deposit' => Notification::where('user_id', $user->id)->ofType('deposit')->count(),
                'withdrawal' => Notification::where('user_id', $user->id)->ofType('withdrawal')->count(),
                'investment' => Notification::where('user_id', $user->id)->ofType('investment')->count(),
                'referral' => Notification::where('user_id', $user->id)->ofType('referral')->count(),
                'payout' => Notification::where('user_id', $user->id)->ofType('payout')->count(),
                'kyc' => Notification::where('user_id', $user->id)->ofType('kyc')->count(),
                'system' => Notification::where('user_id', $user->id)->ofType('system')->count(),
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
