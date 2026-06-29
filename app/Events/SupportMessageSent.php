<?php

namespace App\Events;

use App\Models\SupportMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public SupportMessage $message) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('support-ticket.'.$this->message->support_ticket_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        $this->message->loadMissing('author:id,name,role');

        return [
            'message' => [
                'id'             => $this->message->id,
                'body'           => $this->message->body,
                'user_id'        => $this->message->user_id,
                'author_name'    => $this->message->author->name,
                'author_role'    => $this->message->author->role,
                'is_staff'       => $this->message->author->isStaff(),
                'created_at'     => $this->message->created_at->format('M d, Y g:i A'),
                'created_at_iso' => $this->message->created_at->toIso8601String(),
            ],
        ];
    }
}
