<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use App\Services\SrsWorkflowService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

/**
 * Payments Controller
 *
 * Handles student payment history, real DB invoice/online payment unification,
 * and real-time status synchronization.
 *
 * @package App\Http\Controllers\Student
 * @author Renatus Bernardo
 */
class PaymentsController extends Controller
{
    /**
     * Build the base query scoped strictly to payments owned by the student.
     */
    private function studentPaymentsQuery(int $userId): Builder
    {
        return Payment::withoutGlobalScope('branch')
            ->where(function (Builder $query) use ($userId): void {
                $query->where('user_id', $userId)
                    ->orWhereHas('enrollment', fn (Builder $eq) => $eq->withoutGlobalScope('branch')->where('user_id', $userId))
                    ->orWhereHas('invoice', fn (Builder $iq) => $iq->withoutGlobalScope('branch')->where('user_id', $userId));
            });
    }

    /**
     * Transform a Payment model into display/live-status attributes.
     */
    private function transformPayment(Payment $payment): Payment
    {
        $courseTitle = $payment->enrollment?->course?->title
            ?? $payment->invoice?->course?->title
            ?? $payment->bundle?->title
            ?? 'Tuition / Course Payment';

        $payment->course_title = $courseTitle;
        $payment->invoice_number = $payment->invoice?->invoice_number;
        $payment->control_number = $payment->invoice?->control_number;
        $payment->invoice_print_url = ($payment->invoice_id && Route::has('courses.apply.print'))
            ? route('courses.apply.print', $payment->invoice_id)
            : null;
        $payment->status_label = ucfirst((string) $payment->status);
        $payment->created_at_formatted = optional($payment->payment_date ?? $payment->created_at)?->format('M d, Y h:i A');
        $payment->method_label = ucwords(str_replace('_', ' ', (string) ($payment->payment_method ?: 'offline')));

        $payment->transaction_id = $payment->transaction_id
            ?: ($payment->pesapal_confirmation_code
                ?: ($payment->receipt_number
                    ?: ($payment->pesapal_order_tracking_id
                        ?: ($payment->flutterwave_transaction_id
                            ?: $payment->flutterwave_tx_ref))));

        $payment->status_class = match ($payment->status) {
            Payment::STATUS_COMPLETED => 'graded',
            Payment::STATUS_PENDING => 'pending',
            Payment::STATUS_FAILED, Payment::STATUS_REFUNDED => 'returned',
            default => 'pending',
        };

        $verification = strtolower((string) ($payment->verification_status ?: ($payment->status === Payment::STATUS_COMPLETED ? 'verified' : 'pending')));
        $payment->verification_label = match ($verification) {
            'verified' => 'Verified',
            'rejected' => 'Rejected',
            default => 'Pending Review',
        };
        $payment->verification_class = match ($verification) {
            'verified' => 'badge-verified',
            'rejected' => 'badge-rejected',
            default => 'badge-review',
        };

        $payment->receipt_action_url = ($payment->invoice_id || $payment->status === Payment::STATUS_COMPLETED)
            ? route('student.campus.invoices.receipt.show', $payment->id)
            : $payment->receipt_url;

        return $payment;
    }

    /**
     * Display a listing of the resource records.
     */
    public function index(Request $request): View
    {
        $user = $this->currentUser();
        app(SrsWorkflowService::class)->syncMissingInvoicesForStudent($user);
        $methodOptions = $this->methodOptions($user->id);

        $method = $this->normalizeMethod($request->input('method'), $methodOptions);
        $status = $this->normalizeStatus($request->input('status'));
        $search = trim((string) $request->input('search'));

        $paymentsQuery = $this->filteredPaymentsQuery($user->id, $method, $status, $search);

        $payments = $paymentsQuery
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $payments->getCollection()->transform(fn (Payment $p) => $this->transformPayment($p));

        $summary = $this->buildSummary($user->id);

        return view('student.payments.index', [
            'user' => $user,
            'payments' => $payments,
            'summary' => $summary,
            'methodFilter' => $method,
            'statusFilter' => $status,
            'searchTerm' => $search,
            'methodOptions' => $methodOptions,
            'statusOptions' => [
                '' => 'All statuses',
                Payment::STATUS_PENDING => 'Pending',
                Payment::STATUS_COMPLETED => 'Completed',
                Payment::STATUS_FAILED => 'Failed',
                Payment::STATUS_REFUNDED => 'Refunded',
            ],
        ]);
    }

    /**
     * Real-time JSON snapshot for student payment statuses & verification updates.
     */
    public function liveStatus(Request $request): JsonResponse
    {
        $user = $this->currentUser();
        app(SrsWorkflowService::class)->syncMissingInvoicesForStudent($user);
        $methodOptions = $this->methodOptions($user->id);
        $method = $this->normalizeMethod($request->input('method'), $methodOptions);
        $status = $this->normalizeStatus($request->input('status'));
        $search = trim((string) $request->input('search'));

        $payments = $this->filteredPaymentsQuery($user->id, $method, $status, $search)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(function (Payment $p): array {
                $p = $this->transformPayment($p);

                return [
                    'id' => $p->id,
                    'course_title' => $p->course_title,
                    'invoice_id' => $p->invoice_id,
                    'invoice_number' => $p->invoice_number,
                    'control_number' => $p->control_number,
                    'invoice_print_url' => $p->invoice_print_url,
                    'method_label' => $p->method_label,
                    'status' => $p->status,
                    'status_label' => $p->status_label,
                    'status_class' => $p->status_class,
                    'verification_status' => strtolower((string) ($p->verification_status ?: 'verified')),
                    'verification_label' => $p->verification_label,
                    'verification_class' => $p->verification_class,
                    'amount_formatted' => currency_format((float) $p->amount),
                    'transaction_id' => $p->transaction_id ?? '—',
                    'created_at_formatted' => $p->created_at_formatted ?? '—',
                    'receipt_action_url' => $p->receipt_action_url,
                ];
            })
            ->values();

        $summary = $this->buildSummary($user->id);

        return response()->json([
            'success' => true,
            'synced_at' => now()->format('H:i:s'),
            'summary' => [
                'total_payments' => $summary['total_payments'],
                'completed_payments' => $summary['completed_payments'],
                'pending_payments' => $summary['pending_payments'],
                'failed_payments' => $summary['failed_payments'],
                'refunded_payments' => $summary['refunded_payments'],
                'total_amount_formatted' => currency_format((float) $summary['total_amount']),
            ],
            'payments' => $payments,
        ]);
    }

    /**
     * Build filtered query scoped to student.
     */
    private function filteredPaymentsQuery(int $userId, ?string $method, ?string $status, string $search): Builder
    {
        $paymentsQuery = $this->studentPaymentsQuery($userId)
            ->with([
                'enrollment' => fn ($q) => $q->withoutGlobalScope('branch')->with('course:id,title'),
                'invoice' => fn ($q) => $q->withoutGlobalScope('branch')->with('course:id,title'),
                'bundle:id,title',
                'coupon',
            ]);

        if ($method) {
            $paymentsQuery->where('payment_method', $method);
        }

        if ($status) {
            $paymentsQuery->where('status', $status);
        }

        if ($search !== '') {
            $paymentsQuery->where(function (Builder $sq) use ($search): void {
                $sq->where('transaction_id', 'like', '%' . $search . '%')
                    ->orWhere('receipt_number', 'like', '%' . $search . '%')
                    ->orWhere('pesapal_confirmation_code', 'like', '%' . $search . '%')
                    ->orWhere('pesapal_order_tracking_id', 'like', '%' . $search . '%')
                    ->orWhereHas('enrollment.course', fn (Builder $cq) => $cq->where('title', 'like', '%' . $search . '%'))
                    ->orWhereHas('invoice', fn (Builder $iq) => $iq->withoutGlobalScope('branch')
                        ->where('invoice_number', 'like', '%' . $search . '%')
                        ->orWhere('control_number', 'like', '%' . $search . '%')
                        ->orWhereHas('course', fn (Builder $icq) => $icq->where('title', 'like', '%' . $search . '%')))
                    ->orWhereHas('bundle', fn (Builder $bq) => $bq->where('title', 'like', '%' . $search . '%'));
            });
        }

        return $paymentsQuery;
    }

    /**
     * Build summary.
     */
    private function buildSummary(int $userId): array
    {
        $payments = $this->studentPaymentsQuery($userId)->get();

        if ($payments->isEmpty()) {
            return [
                'total_payments' => 0,
                'completed_payments' => 0,
                'pending_payments' => 0,
                'failed_payments' => 0,
                'refunded_payments' => 0,
                'total_amount' => 0,
            ];
        }

        return [
            'total_payments' => $payments->count(),
            'completed_payments' => $payments->where('status', Payment::STATUS_COMPLETED)->count(),
            'pending_payments' => $payments->where('status', Payment::STATUS_PENDING)->count(),
            'failed_payments' => $payments->where('status', Payment::STATUS_FAILED)->count(),
            'refunded_payments' => $payments->where('status', Payment::STATUS_REFUNDED)->count(),
            'total_amount' => $payments->where('status', Payment::STATUS_COMPLETED)->sum('amount')
                ?: $payments->sum('amount'),
        ];
    }

    /**
     * Method options including all real DB methods used by this student.
     */
    private function methodOptions(int $userId): array
    {
        $defaults = [
            '' => 'All methods',
            Payment::PAYMENT_METHOD_PESAPAL => 'Pesapal',
            Payment::PAYMENT_METHOD_STRIPE => 'Stripe',
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'mobile_money' => 'Mobile Money',
            Payment::PAYMENT_METHOD_OFFLINE => 'Offline / GePG',
            Payment::PAYMENT_METHOD_PAYPAL => 'PayPal',
        ];

        $dbMethods = $this->studentPaymentsQuery($userId)
            ->select('payment_method')
            ->distinct()
            ->pluck('payment_method')
            ->filter();

        foreach ($dbMethods as $m) {
            if (! isset($defaults[$m])) {
                $defaults[$m] = ucwords(str_replace('_', ' ', (string) $m));
            }
        }

        return $defaults;
    }

    /**
     * Normalize method.
     */
    private function normalizeMethod(?string $method, array $methodOptions): ?string
    {
        $method = $method !== null ? trim($method) : null;

        if ($method === null || $method === '') {
            return null;
        }

        return array_key_exists($method, $methodOptions) ? $method : null;
    }

    /**
     * Normalize status.
     */
    private function normalizeStatus(?string $status): ?string
    {
        $status = $status !== null ? trim($status) : null;
        $allowed = [
            Payment::STATUS_PENDING,
            Payment::STATUS_COMPLETED,
            Payment::STATUS_FAILED,
            Payment::STATUS_REFUNDED,
        ];

        if ($status === null || $status === '') {
            return null;
        }

        return in_array($status, $allowed, true) ? $status : null;
    }

    /**
     * Current user.
     */
    private function currentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
