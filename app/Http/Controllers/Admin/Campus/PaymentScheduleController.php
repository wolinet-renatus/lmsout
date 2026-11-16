<?php

namespace App\Http\Controllers\Admin\Campus;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\PaymentSchedule;
use App\Services\InstallmentReminderService;
use Carbon\Carbon;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Installments, Custom Payment Schedules & Due Reminders (migrated from SCMS PaymentScheduleController).
 * @author Renatus Bernardo
 */
class PaymentScheduleController extends Controller
{
    /**
     * Display a listing of the resource records.
     */
    public function index(Request $request)
    {
        $effectiveBranch = auth()->user()?->effectiveBranchId($request->integer('branch_id') ?: null);

        $branchFilter = function ($q) use ($effectiveBranch) {
            if ($effectiveBranch) {
                $q->whereHas('invoice', fn ($i) => $i->where('branch_id', $effectiveBranch));
            }
        };

        $stats = [
            'overdue' => PaymentSchedule::where(fn ($q) => $q->where('status', 'overdue')
                ->orWhere(fn ($w) => $w->whereIn('status', ['pending', 'partial'])->whereDate('due_date', '<', now())))->tap($branchFilter)->count(),
            'due_week' => PaymentSchedule::whereIn('status', ['pending', 'partial'])
                ->whereBetween('due_date', [now()->toDateString(), now()->addDays(7)->toDateString()])->tap($branchFilter)->count(),
            'outstanding' => (float) PaymentSchedule::whereNotIn('status', ['paid', 'cancelled'])->tap($branchFilter)->selectRaw('COALESCE(SUM(amount_due - amount_paid),0) as t')->value('t'),
        ];

        $filter = $request->input('filter', 'open');
        $schedules = PaymentSchedule::with(['invoice.user:id,name,email,phone', 'invoice.course:id,title', 'invoice.branch:id,name'])
            ->whereHas('invoice')
            ->tap($branchFilter)
            ->when($filter === 'open', fn ($q) => $q->whereNotIn('status', ['paid', 'cancelled']))
            ->when($filter === 'overdue', fn ($q) => $q->whereNotIn('status', ['paid', 'cancelled'])->whereDate('due_date', '<', now()))
            ->when($filter === 'paid', fn ($q) => $q->where('status', 'paid'))
            ->when($filter === 'cancelled', fn ($q) => $q->where('status', 'cancelled'))
            ->orderBy('due_date')
            ->paginate(25)
            ->withQueryString();

        $branches = Branch::orderBy('name')->get(['id', 'name']);

        return view('admin.campus.installments.index', compact('schedules', 'stats', 'branches', 'effectiveBranch'));
    }

    /**
     * Show the form for creating a new resource record.
     */
    public function create(Request $request)
    {
        $invoice = $request->filled('invoice_id')
            ? Invoice::with(['user', 'applicant', 'course', 'paymentSchedules'])->findOrFail($request->invoice_id)
            : null;

        $invoices = Invoice::with(['user:id,name,email', 'applicant:id,first_name,last_name,full_name', 'course:id,title'])
            ->where('balance', '>', 0)
            ->latest('id')
            ->get();

        return view('admin.campus.installments.create', compact('invoices', 'invoice'));
    }

    /**
     * Generate.
     */
    public function generate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'invoice_id'    => ['required', 'exists:invoices,id'],
            'template'      => ['required', 'in:weekly,biweekly,monthly,quarterly'],
            'installments'  => ['required', 'integer', 'min:1', 'max:24'],
            'start_date'    => ['required', 'date'],
            'custom_amount' => ['nullable', 'numeric', 'min:1'],
        ]);

        $invoice = Invoice::with('paymentSchedules')->findOrFail($data['invoice_id']);
        $totalAmount = (float) ($data['custom_amount'] ?? $invoice->balance);

        if ($totalAmount <= 0) {
            ToastMagic::error('Invoice has no remaining balance to schedule.');
            return back();
        }

        $count = (int) $data['installments'];
        $start = Carbon::parse($data['start_date']);
        $perAmount = round($totalAmount / $count, 2);
        $remainder = round($totalAmount - ($perAmount * $count), 2);

        DB::transaction(function () use ($invoice, $count, $start, $perAmount, $remainder, $data): void {
            $invoice->paymentSchedules()->whereIn('status', ['pending', 'overdue'])->delete();

            for ($i = 1; $i <= $count; $i++) {
                $dueDate = match ($data['template']) {
                    'weekly'    => $start->copy()->addWeeks($i - 1),
                    'biweekly'  => $start->copy()->addWeeks(($i - 1) * 2),
                    'quarterly' => $start->copy()->addMonths(($i - 1) * 3),
                    default     => $start->copy()->addMonths($i - 1),
                };

                PaymentSchedule::create([
                    'invoice_id'         => $invoice->id,
                    'enrollment_id'      => $invoice->enrollment_id,
                    'installment_number' => $i,
                    'installment_label'  => "Installment {$i} of {$count}",
                    'amount_due'         => $i === $count ? $perAmount + $remainder : $perAmount,
                    'amount_paid'        => 0,
                    'due_date'           => $dueDate->toDateString(),
                    'status'             => 'pending',
                ]);
            }
        });

        ToastMagic::success("Generated {$count} installment schedule(s) for {$invoice->invoice_number}.");

        return redirect()->route('admin.campus.installments.show', $invoice);
    }

    /**
     * Display the specified resource details.
     */
    public function show(Invoice $invoice)
    {
        $invoice->load(['user', 'applicant', 'course', 'branch', 'paymentSchedules' => fn ($q) => $q->orderBy('installment_number')]);

        return view('admin.campus.installments.show', compact('invoice'));
    }

    /**
     * Validate and update the specified resource record.
     */
    public function update(Request $request, PaymentSchedule $schedule): RedirectResponse
    {
        $data = $request->validate([
            'due_date'   => ['required', 'date'],
            'amount_due' => ['required', 'numeric', 'min:0'],
            'status'     => ['required', 'in:pending,partial,paid,overdue,cancelled'],
        ]);

        $schedule->update($data);
        ToastMagic::success("Installment #{$schedule->installment_number} updated.");

        return back();
    }

    /**
     * Mark as sent.
     */
    public function markAsSent(PaymentSchedule $schedule): RedirectResponse
    {
        $schedule->loadMissing('invoice.user', 'invoice.applicant', 'invoice.course');
        $email = $schedule->invoice?->user?->email ?? $schedule->invoice?->applicant?->email;

        if ($email) {
            try {
                Mail::raw(
                    "Reminder: Installment #{$schedule->installment_number} (Balance: " . number_format($schedule->remaining_amount, 2)
                    . ") for " . ($schedule->invoice?->course?->title ?? 'your course')
                    . " is due on " . optional($schedule->due_date)->format('Y-m-d')
                    . ". Invoice: {$schedule->invoice?->invoice_number}, Control #: " . ($schedule->invoice?->control_number ?? 'Pending'),
                    fn ($m) => $m->to($email)->subject("Installment Reminder - {$schedule->invoice?->invoice_number}")
                );
            } catch (\Throwable) {
            }
        }

        $schedule->update([
            'reminder_sent_at' => now(),
            'reminder_count'   => (int) $schedule->reminder_count + 1,
        ]);

        ToastMagic::success("Reminder dispatched and logged for installment #{$schedule->installment_number}.");

        return back();
    }

    /**
     * Check condition: Cancel.
     */
    public function cancel(PaymentSchedule $schedule): RedirectResponse
    {
        if ($schedule->status === 'paid') {
            ToastMagic::error('Paid installments cannot be cancelled.');
            return back();
        }

        $schedule->update(['status' => 'cancelled']);
        ToastMagic::success("Installment #{$schedule->installment_number} cancelled.");

        return back();
    }

    /**
     * Remove the specified resource record from storage.
     */
    public function destroy(PaymentSchedule $schedule): RedirectResponse
    {
        if ((float) $schedule->amount_paid > 0) {
            ToastMagic::error('Cannot delete an installment with recorded payments.');
            return back();
        }

        $schedule->delete();
        ToastMagic::success('Payment schedule entry deleted.');

        return back();
    }

    /**
     * Reminders.
     */
    public function reminders(Request $request)
    {
        $effectiveBranch = auth()->user()?->effectiveBranchId($request->integer('branch_id') ?: null);
        $daysAhead = max(1, (int) $request->input('days', 7));

        $dueSoon = PaymentSchedule::with(['invoice.user:id,name,email,phone', 'invoice.course:id,title', 'invoice.branch:id,name'])
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->whereDate('due_date', '<=', now()->addDays($daysAhead)->toDateString())
            ->when($effectiveBranch, fn ($q) => $q->whereHas('invoice', fn ($i) => $i->where('branch_id', $effectiveBranch)))
            ->orderBy('due_date')
            ->paginate(25)
            ->withQueryString();

        return view('admin.campus.installments.reminders', compact('dueSoon', 'daysAhead'));
    }

    /**
     * Send reminders.
     */
    public function sendReminders(InstallmentReminderService $service): RedirectResponse
    {
        $result = $service->processDueReminders(true);
        ToastMagic::success("{$result['reminders_sent']} reminder(s) sent; {$result['overdue_marked']} installment(s) marked overdue.");

        return back();
    }
}
