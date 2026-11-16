<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Support ticket controller API Controller — handles public and authenticated Sanctum REST endpoints.
 *
 * @author Renatus Bernardo
 */
class SupportTicketController extends Controller
{
    /**
     * Display a listing of the resource records.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $role = $user->isInstructor() ? 'instructor' : 'student';

        $tickets = SupportTicket::withCount('messages')
            ->where('user_id', $user->id)
            ->where('user_role', $role)
            ->orderByDesc('last_reply_at')
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $tickets,
        ]);
    }

    /**
     * Validate and persist a newly created resource record.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'message' => ['required', 'string', 'max:5000'],
            'attachments.*' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,zip,txt'],
        ]);

        $user = $request->user();
        $role = $user->isInstructor() ? 'instructor' : 'student';

        $ticket = DB::transaction(function () use ($validated, $request, $user, $role) {
            $ticket = SupportTicket::create([
                'ticket_number' => SupportTicket::generateTicketNumber(),
                'user_id' => $user->id,
                'user_role' => $role,
                'subject' => $validated['subject'],
                'category' => $validated['category'],
                'priority' => $validated['priority'],
                'status' => 'open',
                'last_reply_at' => now(),
            ]);

            $msg = SupportTicketMessage::create([
                'support_ticket_id' => $ticket->id,
                'sender_id' => $user->id,
                'is_admin_reply' => false,
                'message' => $validated['message'],
            ]);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('support-tickets/' . $ticket->id, 'public');
                    SupportTicketAttachment::create([
                        'support_ticket_message_id' => $msg->id,
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'mime_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }

            return $ticket->load('messages.attachments');
        });

        return response()->json([
            'success' => true,
            'message' => 'Support ticket submitted successfully.',
            'data' => $ticket,
        ], 201);
    }

    /**
     * Display the specified resource details.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $ticket = SupportTicket::with(['messages.sender:id,name,email,role', 'messages.attachments'])
            ->where('user_id', $user->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $ticket,
        ]);
    }

    /**
     * Reply.
     */
    public function reply(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $ticket = SupportTicket::where('user_id', $user->id)->findOrFail($id);

        if ($ticket->status === 'closed') {
            return response()->json([
                'success' => false,
                'message' => 'This ticket is closed.',
            ], 422);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'attachments.*' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,zip,txt'],
        ]);

        $msg = DB::transaction(function () use ($ticket, $validated, $request, $user) {
            $msg = SupportTicketMessage::create([
                'support_ticket_id' => $ticket->id,
                'sender_id' => $user->id,
                'is_admin_reply' => false,
                'message' => $validated['message'],
            ]);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('support-tickets/' . $ticket->id, 'public');
                    SupportTicketAttachment::create([
                        'support_ticket_message_id' => $msg->id,
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'mime_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }

            $ticket->update([
                'status' => 'customer_reply',
                'last_reply_at' => now(),
            ]);

            return $msg->load('attachments');
        });

        return response()->json([
            'success' => true,
            'message' => 'Reply added successfully.',
            'data' => $msg,
        ]);
    }

    /**
     * Close.
     */
    public function close(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $ticket = SupportTicket::where('user_id', $user->id)->findOrFail($id);

        $ticket->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Support ticket closed.',
            'data' => $ticket,
        ]);
    }
}

