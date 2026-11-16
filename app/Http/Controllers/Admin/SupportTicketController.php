<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Admin Support Ticket Controller (v3.0.0)
 *
 * Manages incoming Student & Instructor support tickets, priority filters,
 * file attachments, and admin replies.
 * @author Renatus Bernardo
 */
class SupportTicketController extends Controller
{
    /**
     * Display a listing of the resource records.
     */
    public function index(Request $request): View
    {
        $status   = $request->query('status', '');
        $priority = $request->query('priority', '');
        $role     = $request->query('role', '');
        $search   = trim((string) $request->query('search', ''));

        $query = SupportTicket::with('user')
            ->withCount('messages')
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
            ->orderByDesc('last_reply_at');

        if ($status !== '') {
            $query->where('status', $status);
        }
        if ($priority !== '') {
            $query->where('priority', $priority);
        }
        if ($role !== '') {
            $query->where('sender_role', $role);
        }
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $tickets = $query->paginate(20)->withQueryString();

        $stats = [
            'total'       => SupportTicket::count(),
            'open'        => SupportTicket::where('status', 'open')->count(),
            'in_progress' => SupportTicket::where('status', 'in_progress')->count(),
            'urgent'      => SupportTicket::where('priority', 'urgent')->where('status', '!=', 'closed')->count(),
        ];

        return view('admin.tickets.index', compact('tickets', 'stats', 'status', 'priority', 'role', 'search'));
    }

    /**
     * Display the specified resource details.
     */
    public function show(SupportTicket $ticket): View
    {
        $ticket->load(['user', 'messages.user', 'messages.attachments']);

        return view('admin.tickets.show', compact('ticket'));
    }

    /**
     * Reply.
     */
    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'message'       => ['required', 'string'],
            'status'        => ['nullable', 'in:open,in_progress,answered,closed'],
            'attachments'   => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,zip,txt,csv'],
        ]);

        $msg = SupportTicketMessage::create([
            'ticket_id'      => $ticket->id,
            'user_id'        => auth()->id(),
            'message'        => $validated['message'],
            'is_admin_reply' => true,
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

        $newStatus = $validated['status'] ?? 'answered';

        $ticket->update([
            'status'        => $newStatus,
            'last_reply_at' => now(),
            'closed_at'     => $newStatus === 'closed' ? now() : null,
        ]);

        ToastMagic::success("Reply posted to ticket #{$ticket->ticket_number}.");

        return redirect()->route('admin.tickets.show', $ticket);
    }

    /**
     * Update status.
     */
    public function updateStatus(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'status'   => ['required', 'in:open,in_progress,answered,closed'],
            'priority' => ['nullable', 'in:low,medium,high,urgent'],
        ]);

        $ticket->update([
            'status'    => $validated['status'],
            'priority'  => $validated['priority'] ?? $ticket->priority,
            'closed_at' => $validated['status'] === 'closed' ? now() : null,
        ]);

        ToastMagic::success("Ticket #{$ticket->ticket_number} updated.");

        return back();
    }

    /**
     * Remove the specified resource record from storage.
     */
    public function destroy(SupportTicket $ticket): RedirectResponse
    {
        $num = $ticket->ticket_number;
        $ticket->delete();

        ToastMagic::success("Ticket #{$num} deleted.");

        return redirect()->route('admin.tickets.index');
    }
}

