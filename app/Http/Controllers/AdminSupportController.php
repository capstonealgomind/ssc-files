<?php

namespace App\Http\Controllers;

use App\Events\SupportMessageSent;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminSupportController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->isStaff(), 403);

        $status = $request->query('status');

        return Inertia::render('Support/Index', [
            'tickets'        => $this->adminTickets($status),
            'statuses'       => $this->statusFilters(),
            'filter'         => $status,
            'selectedTicket' => null,
        ]);
    }

    public function show(Request $request, SupportTicket $ticket): Response
    {
        abort_unless($request->user()->isStaff(), 403);

        $ticket->load([
            'user:id,name,email,student_id_number,voter_id_number',
            'messages.author:id,name,role',
            'assignee:id,name',
            'approver:id,name',
        ]);

        $status = $request->query('status');

        return Inertia::render('Support/Index', [
            'tickets'        => $this->adminTickets($status),
            'statuses'       => $this->statusFilters(),
            'filter'         => $status,
            'selectedTicket' => $this->formatTicketDetail($ticket),
        ]);
    }

    public function approve(Request $request, SupportTicket $ticket): RedirectResponse
    {
        abort_unless($request->user()->isStaff(), 403);
        abort_unless($ticket->status === SupportTicket::STATUS_PENDING, 422);

        $ticket->update([
            'status'      => SupportTicket::STATUS_APPROVED,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'assigned_to' => $ticket->assigned_to ?? $request->user()->id,
        ]);

        return back()->with('success', 'Ticket approved. You can now message the voter.');
    }

    public function reject(Request $request, SupportTicket $ticket): RedirectResponse
    {
        abort_unless($request->user()->isStaff(), 403);
        abort_unless($ticket->status === SupportTicket::STATUS_PENDING, 422);

        $ticket->update([
            'status'    => SupportTicket::STATUS_REJECTED,
            'closed_at' => now(),
        ]);

        return back()->with('success', 'Ticket rejected.');
    }

    public function close(Request $request, SupportTicket $ticket): RedirectResponse
    {
        abort_unless($request->user()->isStaff(), 403);
        abort_unless($ticket->status === SupportTicket::STATUS_APPROVED, 422);

        $ticket->update([
            'status'    => SupportTicket::STATUS_CLOSED,
            'closed_at' => now(),
        ]);

        return back()->with('success', 'Ticket closed.');
    }

    public function storeMessage(Request $request, SupportTicket $ticket): RedirectResponse
    {
        abort_unless($request->user()->isStaff(), 403);
        abort_unless($ticket->isChatEnabled(), 403, 'Approve the ticket before messaging the voter.');

        $validated = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $message = $this->createMessage($ticket, $request->user()->id, $validated['body']);

        SupportMessageSent::dispatch($message);

        return back()->with('success', 'Message sent.');
    }

    private function createMessage(SupportTicket $ticket, int $userId, string $body): SupportMessage
    {
        $message = SupportMessage::query()->create([
            'support_ticket_id' => $ticket->id,
            'user_id'           => $userId,
            'body'              => $body,
        ]);

        $ticket->update(['last_message_at' => now()]);

        return $message->load('author:id,name,role');
    }

    private function adminTickets(?string $status): array
    {
        return SupportTicket::query()
            ->with(['user:id,name,email,student_id_number', 'assignee:id,name'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest('updated_at')
            ->get()
            ->map(fn (SupportTicket $ticket) => $this->formatTicketSummary($ticket))
            ->values()
            ->all();
    }

    private function formatTicketSummary(SupportTicket $ticket): array
    {
        return [
            'id'              => $ticket->id,
            'ticket_number'   => $ticket->ticket_number,
            'subject'         => $ticket->subject,
            'category'        => $ticket->category,
            'category_label'  => SupportTicket::CATEGORIES[$ticket->category] ?? $ticket->category,
            'status'          => $ticket->status,
            'status_label'    => $this->statusLabel($ticket->status),
            'can_chat'        => $ticket->isChatEnabled(),
            'last_message_at' => $ticket->last_message_at?->format('M d, Y g:i A'),
            'created_at'      => $ticket->created_at->format('M d, Y g:i A'),
            'voter'           => $ticket->user ? [
                'name'              => $ticket->user->name,
                'email'             => $ticket->user->email,
                'student_id_number' => $ticket->user->student_id_number,
            ] : null,
            'assignee_name'   => $ticket->assignee?->name,
        ];
    }

    private function formatTicketDetail(SupportTicket $ticket): array
    {
        return array_merge($this->formatTicketSummary($ticket), [
            'approved_at' => $ticket->approved_at?->format('M d, Y g:i A'),
            'approved_by' => $ticket->approver?->name,
            'voter'       => $ticket->user ? [
                'name'              => $ticket->user->name,
                'email'             => $ticket->user->email,
                'student_id_number' => $ticket->user->student_id_number,
                'voter_id_number'   => $ticket->user->voter_id_number,
            ] : null,
            'messages' => $ticket->messages->map(fn (SupportMessage $message) => $this->formatMessage($message))->values()->all(),
        ]);
    }

    private function formatMessage(SupportMessage $message): array
    {
        return [
            'id'             => $message->id,
            'body'           => $message->body,
            'user_id'        => $message->user_id,
            'author_name'    => $message->author->name,
            'author_role'    => $message->author->role,
            'is_staff'       => $message->author->isStaff(),
            'created_at'     => $message->created_at->format('M d, Y g:i A'),
            'created_at_iso' => $message->created_at->toIso8601String(),
        ];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            SupportTicket::STATUS_PENDING  => 'Pending Review',
            SupportTicket::STATUS_APPROVED => 'Approved',
            SupportTicket::STATUS_REJECTED => 'Rejected',
            SupportTicket::STATUS_CLOSED   => 'Closed',
            default                        => ucfirst($status),
        };
    }

    private function statusFilters(): array
    {
        return [
            ['value' => null, 'label' => 'All'],
            ['value' => SupportTicket::STATUS_PENDING, 'label' => 'Pending'],
            ['value' => SupportTicket::STATUS_APPROVED, 'label' => 'Approved'],
            ['value' => SupportTicket::STATUS_CLOSED, 'label' => 'Closed'],
            ['value' => SupportTicket::STATUS_REJECTED, 'label' => 'Rejected'],
        ];
    }
}
