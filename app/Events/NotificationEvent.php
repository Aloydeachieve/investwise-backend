<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Notification $notification;
    public int $unreadCount;

    /**
     * Create a new event instance.
     */
    public function __construct(Notification $notification, int $unreadCount = 0)
    {
        $this->notification = $notification;
        $this->unreadCount = $unreadCount;
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'title' => $this->notification->title,
            'message' => $this->notification->message,
            'type' => $this->notification->type,
            'is_read' => $this->notification->is_read,
            'for_admin' => $this->notification->for_admin,
            'meta' => $this->notification->meta,
            'created_at' => $this->notification->created_at->toISOString(),
            'formatted_created_at' => $this->notification->formatted_created_at,
            'unread_count' => $this->unreadCount,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        if ($this->notification->for_admin) {
            return [
                new PrivateChannel('admin-notifications'),
            ];
        }

        return [
            new PrivateChannel('user-notifications.' . $this->notification->user_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'notification.created';
    }
}
