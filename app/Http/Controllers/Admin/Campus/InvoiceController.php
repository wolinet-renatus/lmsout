<?php

namespace App\Http\Controllers\Admin\Campus;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\QrCodeService;
use App\Services\SrsWorkflowService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;

/**
 * Invoice controller — manages campus & short-course operations within the admin portal.
 *
 * @author Renatus Bernardo
 */
class InvoiceController extends Controller
{
    /**
     * Initialize dependencies for this class.
     */
    public function __construct(private SrsWorkflowService $srs)
    {
    }

    /**
     * Display a listing of the resource records.
     */
    public function index(Request $request)
    {
        $base = Invoice::query();
        $stats = [
            'total' => (clone $base)->count(),
            'unpaid' => (clone $base)->where('payment_status', 'unpaid')->count(),
            'partial' => (clone $base)->where('payment_status', 'partial')->count(),
            'paid' => (clone $base)->where('payment_status', 'paid')->count(),
            'outstanding' => (float) (clone $base)->sum('balance'),
            'collected' => (float) (clone $base)->sum('paid_amount'),
        ];

        $invoices = Invoice::with(['user:id,name,email', 'applicant:id,full_name', 'course:id,title', 'branch:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->where('payment_status', $request->status))
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = '%' . $request->search . '%';
                $q->where(fn ($w) => $w->where('invoice_number', 'like', $s)->orWhere('control_number', 'like', $s)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $s)->orWhere('email', 'like', $s)));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $branches = Branch::orderBy('name')->get(['id', 'name']);
        $pendingVerifications = Payment::whereNotNull('invoice_id')->where('verification_status', 'pending')->count();

        return view('admin.campus.invoices.index', compact('invoices', 'stats', 'branches', 'pendingVerifications'));
    }

    /**
     * Display the specified resource details.
     */
    public function show(Invoice $invoice)
    {
        $invoice->load(['user', 'applicant.country', 'course', 'branch', 'enrollment.venue', 'paymentSchedules', 'payments' => fn ($q) => $q->latest()]);

        return view('admin.campus.invoices.show', compact('invoice'));
    }

    /**
     * Record payment.
     */
    public function recordPayment(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:' . max(1, (float) $invoice->balance)],
            'payment_method' => ['required', 'in:control_number,cash,bank_transfer,mobile_money'],
            'receipt_number' => ['nullable', 'string', 'max:100'],
            'payment_date' => ['nullable', 'date'],
            'receipt' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:4096'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $receiptPath = $request->hasFile('receipt') ? $request->file('receipt')->store('campus/receipts', 'public') : null;

        $this->srs->applyPaymentToInvoice($invoice, [
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'receipt_number' => $data['receipt_number'] ?? null,
            'payment_date' => $data['payment_date'] ?? now()->toDateString(),
            'receipt_file' => $receiptPath,
            'notes' => $data['notes'] ?? 'Recorded by ' . $request->user()->name,
            'verify_immediately' => true,
        ]);

        ToastMagic::success('Payment recorded and invoice updated.');

        return back();
    }

    /**
     * Verify payment.
     */
    public function verifyPayment(Request $request, Payment $payment)
    {
        $action = $request->validate(['action' => ['required', 'in:approve,reject']])['action'];

        if ($action === 'approve') {
            $this->srs->verifyPendingPayment($payment);
            ToastMagic::success('Payment verified. Invoice balance updated.');
        } else {
            $payment->update(['verification_status' => 'rejected', 'status' => Payment::STATUS_FAILED]);
            ToastMagic::warning('Payment proof rejected. The student can re-upload from their portal.');
        }

        return back();
    }

    /**
     * Regenerate control number.
     */
    public function regenerateControlNumber(Invoice $invoice)
    {
        if ($invoice->payment_status === 'paid') {
            ToastMagic::error('Invoice is fully paid.');
            return back();
        }

        $invoice->update(['control_number' => $this->srs->generateControlNumber(), 'control_number_generated_at' => now()]);
        ToastMagic::success('New control number issued.');

        return back();
    }

    /**
     * Printable invoice (browser print / save as PDF) with QR verification code.
     */
    public function print(Invoice $invoice)
    {
        $invoice->load(['user', 'applicant', 'course', 'branch', 'enrollment.venue', 'paymentSchedules', 'payments']);
        $qr = QrCodeService::dataUri(route('campus.invoice.verify', ['number' => $invoice->invoice_number]), 140);

        return view('campus.invoice-print', compact('invoice', 'qr'));
    }

    /**
     * Printable Official Payment Receipt (migrated from SCMS payments/receipt).
     */
    public function receipt(Payment $payment)
    {
        $payment->load(['invoice.branch', 'invoice.course', 'invoice.user', 'invoice.applicant', 'user', 'applicant']);

        return view('campus.payment-receipt', compact('payment'));
    }
}
