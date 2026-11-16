<?php

namespace App\Services;

use App\Models\NotificationTemplate;
use App\Models\PaymentSchedule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Installment reminder service — encapsulates domain business logic and transactional operations.
 *
 * @author Renatus Bernardo
 */
class InstallmentReminderService
{
    /**
     * Update overdue statuses and dispatch installment reminder emails using templates.
     *
     * @return array{overdue_marked: int, reminders_sent: int}
     */
    public function processDueReminders(bool $sendEmails = true, int $daysAhead = 3): array
    {
        $overdueMarked = PaymentSchedule::whereIn('status', ['pending', 'partial'])
            ->whereDate('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);

        $schedules = PaymentSchedule::with(['invoice.user', 'invoice.applicant', 'invoice.course'])
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->whereDate('due_date', '<=', now()->addDays($daysAhead)->toDateString())
            ->where(function ($q): void {
                $q->whereNull('reminder_sent_at')
                    ->orWhere('reminder_sent_at', '<=', now()->subDay());
            })
            ->get();

        $upcomingTpl = NotificationTemplate::where('key', 'installment_reminder')->where('is_active', true)->first();
        $overdueTpl = NotificationTemplate::where('key', 'overdue_notice')->where('is_active', true)->first();

        $remindersSent = 0;

        foreach ($schedules as $schedule) {
            $invoice = $schedule->invoice;
            if (! $invoice) {
                continue;
            }

            $email = $invoice->user?->email ?? $invoice->applicant?->email;
            $studentName = $invoice->user?->name ?? $invoice->applicant?->full_name ?? 'Learner';
            if (! $email) {
                continue;
            }

            $isOverdue = $schedule->status === 'overdue' || $schedule->isOverdue();
            $tpl = $isOverdue ? ($overdueTpl ?? $upcomingTpl) : ($upcomingTpl ?? $overdueTpl);

            $subject = $isOverdue
                ? "Overdue Installment Notice - {$invoice->invoice_number}"
                : "Installment Payment Reminder - {$invoice->invoice_number}";
            $body = "Hello {$studentName},\n\nInstallment #{$schedule->installment_number} (" . number_format((float) $schedule->remaining_amount, 2) . ") for "
                . ($invoice->course?->title ?? 'your course')
                . " is due on " . optional($schedule->due_date)->format('Y-m-d')
                . ".\nControl Number: " . ($invoice->control_number ?? 'Pending')
                . "\nInvoice: {$invoice->invoice_number}";

            if ($tpl) {
                $rendered = $tpl->render([
                    'student_name' => $studentName,
                    'course_title' => $invoice->course?->title ?? 'Short Course',
                    'installment_number' => $schedule->installment_number,
                    'amount_due' => number_format((float) $schedule->remaining_amount, 2),
                    'due_date' => optional($schedule->due_date)->format('Y-m-d') ?? '',
                    'control_number' => $invoice->control_number ?? 'N/A',
                    'invoice_number' => $invoice->invoice_number,
                    'balance' => number_format((float) $invoice->balance, 2),
                ]);
                $subject = $rendered['subject'] ?: $subject;
                $body = $rendered['body'] ?: $body;
            }

            if ($sendEmails) {
                try {
                    Mail::raw($body, function ($message) use ($email, $subject): void {
                        $message->to($email)->subject($subject);
                    });
                } catch (\Throwable $e) {
                    Log::warning('Installment reminder mail failed', [
                        'schedule_id' => $schedule->id,
                        'email' => $email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $schedule->update([
                'reminder_sent_at' => now(),
                'reminder_count' => (int) $schedule->reminder_count + 1,
            ]);
            $remindersSent++;
        }

        return [
            'overdue_marked' => $overdueMarked,
            'reminders_sent' => $remindersSent,
        ];
    }
}
