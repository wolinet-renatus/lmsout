<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Student Support Ticket Controller (v3.0.0)
 */
class SupportTicketController extends Controller
{
    public function index(): View
    {
        $tickets = SupportTicket::where('user_id', auth()->id())
            ->withCount('messages')
            ->orderByDesc('last_reply_at')
            ->paginate(15);

        return view('student.tickets.index', compact('tickets'));
    }

    public function create(): View
    {
        return view('student.tickets.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject'       => ['required', 'string', 'max:255'],
            'department'    => ['required', 'string', 'in:general,technical,billing,course_review'],
            'priority'      => ['required', 'in:low,medium,high,urgent'],
            'message'       => ['required', 'string'],
            'attachments'   => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,zip,txt,csv'],
        ]);

        $ticket = SupportTicket::create([
            'user_id'       => auth()->id(),
            'sender_role'   => 'student',
            'subject'       => $validated['subject'],
            'department'    => $validated['department'],
            'priority'      => $validated['priority'],
            'status'        => 'open',
            'last_reply_at' => now(),
        ]);

        $msg = SupportTicketMessage::create([
            'ticket_id'      => $ticket->id,
            'user_id'        => auth()->id(),
            'message'        => $validated['message'],
            'is_admin_reply' => false,
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('support-tickets/' . $ticket->id, 'public');
                SupportTicketAttachment::create([
                    'ticket_id'     => $ticket->id,
                    'message_id'    => $msg->id,
                    'file_path'     => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type'     => $file->getClientMimeType(),
                    'file_size'     => $file->getSize(),
                ]);
            }
        }

        return redirect()
            ->route('student.tickets.show', $ticket)
            ->with('success', "Support ticket #{$ticket->ticket_number} submitted successfully.");
    }

    public function show(SupportTicket $ticket): View
    {
        abort_unless((int) $ticket->user_id === (int) auth()->id(), 403);

        $ticket->load(['messages.user', 'messages.attachments']);

        return view('student.tickets.show', compact('ticket'));
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        abort_unless((int) $ticket->user_id === (int) auth()->id(), 403);

        $validated = $request->validate([
            'message'       => ['required', 'string'],
            'attachments'   => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,zip,txt,csv'],
        ]);

        $msg = SupportTicketMessage::create([
            'ticket_id'      => $ticket->id,
            'user_id'        => auth()->id(),
            'message'        => $validated['message'],
            'is_admin_reply' => false,
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('support-tickets/' . $ticket->id, 'public');
                SupportTicketAttachment::create([
                    'ticket_id'     => $ticket->id,
                    'message_id'    => $msg->id,
                    'file_path'     => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type'     => $file->getClientMimeType(),
                    'file_size'     => $file->getSize(),
                ]);
            }
        }

        $ticket->update([
            'status'        => 'open',
            'last_reply_at' => now(),
            'closed_at'     => null,
        ]);

        return redirect()
            ->route('student.tickets.show', $ticket)
            ->with('success', 'Your reply has been sent.');
    }

    public function close(SupportTicket $ticket): RedirectResponse
    {
        abort_unless((int) $ticket->user_id === (int) auth()->id(), 403);

        $ticket->update([
            'status'    => 'closed',
            'closed_at' => now(),
        ]);

        return redirect()
            ->route('student.tickets.show', $ticket)
            ->with('success', "Ticket #{$ticket->ticket_number} closed.");
    }
}

