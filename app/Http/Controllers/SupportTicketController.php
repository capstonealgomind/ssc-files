<?php

namespace App\Http\Controllers;

use App\Events\SupportMessageSent;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SupportTicketController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->role === 'voter', 403);

        $tickets = $this->voterTickets($request);

        return Inertia::render('HelpSupport', [
            'tickets'        => $tickets,
            'categories'     => SupportTicket::CATEGORIES,
            'selectedTicket' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === 'voter', 403);

        $validated = $request->validate([
            'subject'  => 'required|string|max:255',
            'category' => ['required', Rule::in(array_keys(SupportTicket::CATEGORIES))],
            'message'  => 'required|string|max:5000',
        ]);

        $ticket = SupportTicket::query()->create([
            'ticket_number'   => SupportTicket::generateTicketNumber(),
            'user_id'         => $request->user()->id,
            'subject'         => $validated['subject'],
            'category'        => $validated['category'],
            'status'          => SupportTicket::STATUS_PENDING,
            'last_message_at' => now(),
        ]);

        SupportMessage::query()->create([
            'support_ticket_id' => $ticket->id,
            'user_id'           => $request->user()->id,
            'body'              => $validated['message'],
        ]);

        return redirect()
            ->route('help.tickets.show', $ticket)
            ->with('success', 'Ticket submitted. Your support request has been sent for review.');
    }

    public function show(Request $request, SupportTicket $ticket): Response
    {
        $this->authorizeVoterTicket($request, $ticket);

        $ticket->load([
            'messages.author:id,name,role',
            'approver:id,name',
        ]);

        return Inertia::render('HelpSupport', [
            'tickets'        => $this->voterTickets($request),
            'categories'     => SupportTicket::CATEGORIES,
            'selectedTicket' => $this->formatTicketDetail($ticket),
        ]);
    }

    public function storeMessage(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $this->authorizeVoterTicket($request, $ticket);

        abort_unless($ticket->isChatEnabled(), 403, 'This ticket is not open for replies yet.');

        $validated = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $message = $this->createMessage($ticket, $request->user()->id, $validated['body']);

        SupportMessageSent::dispatch($message);

        return back()->with('success', 'Message sent.');
    }

    private function voterTickets(Request $request): array
    {
        return $request->user()
            ->supportTickets()
            ->latest('updated_at')
            ->get()
            ->map(fn (SupportTicket $ticket) => $this->formatTicketSummary($ticket))
            ->values()
            ->all();
    }

    private function authorizeVoterTicket(Request $request, SupportTicket $ticket): void
    {
        abort_unless($request->user()->role === 'voter', 403);
        abort_unless($ticket->user_id === $request->user()->id, 403);
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
        ];
    }

    private function formatTicketDetail(SupportTicket $ticket): array
    {
        return array_merge($this->formatTicketSummary($ticket), [
            'approved_at' => $ticket->approved_at?->format('M d, Y g:i A'),
            'approved_by' => $ticket->approver?->name,
            'messages'    => $ticket->messages->map(fn (SupportMessage $message) => $this->formatMessage($message))->values()->all(),
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
}
