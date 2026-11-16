<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Instructor Support Ticket Controller (v3.0.0)
 * @author Renatus Bernardo
 */
class SupportTicketController extends Controller
{
    /**
     * Display a listing of the resource records.
     */
    public function index(Request $request): View|JsonResponse
    {
        $userId = auth()->id();
        $status = (string) $request->input('status', 'all');
        $department = (string) $request->input('department', 'all');
        $search = trim((string) $request->input('search', ''));
        $sort = (string) $request->input('sort', 'latest');
        $perPage = max(3, (int) $request->input('per_page', 3));

        $baseQuery = SupportTicket::where('user_id', $userId);
        if ($department !== 'all' && $department !== '') {
            $baseQuery->where('department', $department);
        }

        $statusCounts = [
            'all' => (clone $baseQuery)->count(),
            'open' => (clone $baseQuery)->where('status', '!=', 'closed')->count(),
            'closed' => (clone $baseQuery)->where('status', 'closed')->count(),
        ];

        $ticketsQuery = SupportTicket::where('user_id', $userId)
            ->withCount('messages');

        if ($department !== 'all' && $department !== '') {
            $ticketsQuery->where('department', $department);
        }

        if ($status === 'open') {
            $ticketsQuery->where('status', '!=', 'closed');
        } elseif ($status === 'closed') {
            $ticketsQuery->where('status', 'closed');
        }

        if ($search !== '') {
            $ticketsQuery->where(function ($q) use ($search) {
                $q->where('subject', 'like', '%' . $search . '%')
                    ->orWhere('ticket_number', 'like', '%' . $search . '%');
            });
        }

        switch ($sort) {
            case 'oldest':
                $ticketsQuery->orderBy('last_reply_at', 'asc');
                break;
            case 'priority':
                $ticketsQuery->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")->orderByDesc('last_reply_at');
                break;
            case 'latest':
            default:
                $ticketsQuery->orderByDesc('last_reply_at');
                break;
        }

        $tickets = $ticketsQuery->paginate($perPage)->withQueryString();

        if ($request->ajax() || $request->wantsJson()) {
            $html = view('instructor.partials.tickets-list', [
                'tickets' => $tickets,
                'currentSearch' => $search,
                'currentStatus' => $status,
                'currentDepartment' => $department,
            ])->render();

            return response()->json([
                'html' => $html,
                'pagination' => (string) $tickets->links(),
                'statusCounts' => $statusCounts,
                'from' => $tickets->firstItem() ?? 0,
                'to' => $tickets->lastItem() ?? 0,
                'total' => $tickets->total(),
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
            ]);
        }

        return view('instructor.tickets.index', [
            'tickets' => $tickets,
            'statusCounts' => $statusCounts,
            'currentStatus' => $status,
            'currentDepartment' => $department,
            'currentSearch' => $search,
            'currentSort' => $sort,
            'currentPerPage' => $perPage,
        ]);
    }

    /**
     * Show the form for creating a new resource record.
     */
    public function create(): View
    {
        return view('instructor.tickets.create');
    }

    /**
     * Validate and persist a newly created resource record.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject'       => ['required', 'string', 'max:255'],
            'department'    => ['required', 'string', 'in:general,technical,billing,course_review,payout'],
            'priority'      => ['required', 'in:low,medium,high,urgent'],
            'message'       => ['required', 'string'],
            'attachments'   => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,zip,txt,csv'],
        ]);

        $ticket = SupportTicket::create([
            'user_id'       => auth()->id(),
            'sender_role'   => 'instructor',
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
            ->route('instructor.tickets.show', $ticket)
            ->with('success', "Support ticket #{$ticket->ticket_number} submitted successfully.");
    }

    /**
     * Display the specified resource details.
     */
    public function show(SupportTicket $ticket): View
    {
        abort_unless((int) $ticket->user_id === (int) auth()->id(), 403);

        $ticket->load(['messages.user', 'messages.attachments']);

        return view('instructor.tickets.show', compact('ticket'));
    }

    /**
     * Reply.
     */
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
            ->route('instructor.tickets.show', $ticket)
            ->with('success', 'Your reply has been sent.');
    }

    /**
     * Close.
     */
    public function close(SupportTicket $ticket): RedirectResponse
    {
        abort_unless((int) $ticket->user_id === (int) auth()->id(), 403);

        $ticket->update([
            'status'    => 'closed',
            'closed_at' => now(),
        ]);

        return redirect()
            ->route('instructor.tickets.show', $ticket)
            ->with('success', "Ticket #{$ticket->ticket_number} closed.");
    }
}

