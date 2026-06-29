<?php

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('support-ticket.{ticketId}', function (User $user, int $ticketId) {
    $ticket = SupportTicket::query()->find($ticketId);

    if (!$ticket) {
        return false;
    }

    return $user->isStaff() || $ticket->user_id === $user->id;
});
