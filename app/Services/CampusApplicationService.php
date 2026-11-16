<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Single source of truth for Face-to-Face / Hybrid intake (SCMS Applicants Form),
 * shared by the Admin/Registrar desk and the public self-application page.
 *
 * @package App\Services
 * @author Renatus Bernardo
 */
class CampusApplicationService
{
    /**
     * Inject SRS workflow service for fee calculation, control numbers, and installment splits.
     */
    public function __construct(private SrsWorkflowService $srs)
    {
    }

    /**
     * Validation rules mirroring the SCMS Applicants Form sections 1, 1.1, 2 and 3.
     *
     * @return array<string, mixed>
     */
    public static function rules(bool $public = false): array
    {
        return [
            // 1. Applicant Information
            'full_name' => ['required', 'string', 'max:255'],
            'home_address' => ['required', 'string', 'max:255'],
            'country_id' => ['required', 'exists:countries,id'],
            'office_of_work' => ['nullable', 'string', 'max:255'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:30'],
            'email' => [$public ? 'required' : 'nullable', 'email', 'max:255'],
            'name_of_sponsor' => ['nullable', 'string', 'max:255'],
            'phone_2' => ['nullable', 'string', 'max:30'],
            'education_level' => ['required', 'in:' . implode(',', array_keys(Applicant::educationLevels()))],
            'gender' => ['nullable', 'in:male,female'],
            // 1.1 Staff Student Validation
            'is_staff_student' => ['nullable', 'boolean'],
            'parent_staff_id' => ['nullable', 'required_if:is_staff_student,1', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:4096'],
            // 2. Course Details
            'course_id' => ['required', 'exists:courses,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'session' => ['required', 'in:morning,afternoon,evening,weekend'],
            'venue_id' => ['nullable', 'exists:venues,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'duration' => ['nullable', 'in:' . implode(',', array_keys(Applicant::durations()))],
            'custom_duration' => ['nullable', 'integer', 'min:1', 'max:104'],
            // 3. Payment Details
            'installment_type' => ['required', 'in:quarter,half,full'],
            'apply_discount' => ['nullable', 'in:0,1'],
            'discount_reason' => ['nullable', 'required_if:apply_discount,1', 'in:' . implode(',', array_keys(Applicant::discountReasons()))],
            'discount_attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:4096'],
            'payment_option' => ['nullable', 'in:offline,online,pay_later'],
            'receipt' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:4096'],
            'receipt_number' => ['nullable', 'required_with:receipt', 'string', 'max:100'],
            'amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * Process an application and return the created artefacts.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, UploadedFile|null>  $files
     * @return array{applicant: Applicant, user: User, enrollment: Enrollment, invoice: Invoice, payment: ?\App\Models\Payment, created_user: bool, plain_password: ?string}
     */
    public function submit(array $data, array $files = [], string $source = 'admin', ?User $actor = null): array
    {
        $course = Course::findOrFail($data['course_id']);
        if (! $course->requiresApplicantForm()) {
            throw new \InvalidArgumentException('Online courses are purchased through direct online checkout.');
        }

        $phone = validatePhoneNumber($data['mobile'] ?? null);
        if ($phone === false) {
            throw new \InvalidArgumentException('Invalid mobile number. Use format 0712345678 or +255712345678.');
        }

        $venue = ! empty($data['venue_id']) ? Venue::withoutGlobalScope('branch')->find($data['venue_id']) : null;
        $branchId = $data['branch_id'] ?? $venue?->branch_id ?? $course->default_branch_id ?? $actor?->branch_id;

        $weeks = (int) ($data['custom_duration'] ?? 0) ?: (int) ($data['duration'] ?? 0) ?: $this->weeksFromLabel($course->duration_label);
        $start = Carbon::parse($data['start_date']);
        $end = ! empty($data['end_date']) ? Carbon::parse($data['end_date']) : ($weeks > 0 ? $start->copy()->addWeeks($weeks) : null);

        return DB::transaction(function () use ($data, $files, $source, $actor, $course, $phone, $venue, $branchId, $weeks, $start, $end) {
            // ---- Resolve or create learner account (single unified User; no duplicate auth tables)
            $plainPassword = null;
            $createdUser = false;
            $email = ! empty($data['email']) ? Str::lower(trim($data['email'])) : null;

            $user = $email ? User::where('email', $email)->first() : null;
            if (! $user) {
                $user = User::where('phone', $phone)->where('role', 'student')->first();
            }

            if (! $user) {
                $plainPassword = $source === 'public' && ! empty($data['password']) ? $data['password'] : Str::random(10);
                $user = User::create([
                    'name' => $data['full_name'],
                    'email' => $email ?? ('applicant' . Str::lower(Str::random(8)) . '@campus.local'),
                    'password' => Hash::make($plainPassword),
                    'role' => 'student',
                    'phone' => $phone,
                    'address' => $data['home_address'] ?? null,
                    'branch_id' => $branchId,
                    'is_approved' => true,
                    'force_password_reset' => $source !== 'public',
                    'password_changed_at' => now(),
                ]);
                if (Role::where('name', 'student')->exists()) {
                    $user->assignRole('student');
                }
                $createdUser = true;
            }

            // ---- Uploads
            $parentStaffPath = isset($files['parent_staff_id']) && $files['parent_staff_id']
                ? $files['parent_staff_id']->store('campus/staff-ids', 'public') : null;
            $discountPath = isset($files['discount_attachment']) && $files['discount_attachment']
                ? $files['discount_attachment']->store('campus/discounts', 'public') : null;
            $receiptPath = isset($files['receipt']) && $files['receipt']
                ? $files['receipt']->store('campus/receipts', 'public') : null;

            $isStaffStudent = (bool) ($data['is_staff_student'] ?? false);
            $applyDiscount = (string) ($data['apply_discount'] ?? '0') === '1';

            // ---- Applicant record (exact SCMS form fields)
            $applicant = Applicant::create([
                'user_id' => $user->id,
                'branch_id' => $branchId,
                'country_id' => $data['country_id'] ?? null,
                'full_name' => $data['full_name'],
                'home_address' => $data['home_address'] ?? null,
                'office_of_work' => $data['office_of_work'] ?? null,
                'occupation' => $data['occupation'] ?? null,
                'email' => $email,
                'phone' => $phone,
                'name_of_sponsor' => $data['name_of_sponsor'] ?? null,
                'sponsor_phone' => $data['phone_2'] ?? null,
                'gender' => $data['gender'] ?? null,
                'education_level' => $data['education_level'] ?? null,
                'is_staff_student' => $isStaffStudent,
                'parent_staff_id_path' => $parentStaffPath,
                'has_discount' => $applyDiscount,
                'discount_reason' => $applyDiscount ? ($data['discount_reason'] ?? null) : ($isStaffStudent ? 'staff_student_50' : null),
                'discount_attachment_path' => $discountPath,
                'source' => $source,
            ]);

            // ---- Fee calculation (Staff Student 50% for Basic Computer Application; course discount when applied)
            $fees = $this->srs->calculateFees($course, $isStaffStudent, false, 0.0);
            if (! $applyDiscount && ! ($isStaffStudent && Str::contains(Str::lower($course->title), 'basic computer'))) {
                // No discount selected: charge the full course fee
                $base = (float) ($course->regular_price ?: $course->sale_price ?: 0);
                $fees = ['total_amount' => $base, 'discount_amount' => 0.0, 'net_amount' => $base, 'discount_percentage' => 0.0];
            }
            $applicant->update(['discount_percentage' => $fees['discount_percentage']]);

            $installmentPlan = $isStaffStudent && Str::contains(Str::lower($course->title), 'basic computer')
                ? 'full' : ($data['installment_type'] ?? 'full');

            // ---- Enrollment (cohort-based; multiple cohorts per course allowed)
            $enrollment = Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'applicant_id' => $applicant->id,
                'branch_id' => $branchId,
                'venue_id' => $venue?->id,
                'course_assignment_id' => $data['course_assignment_id'] ?? null,
                'status' => $fees['net_amount'] <= 0 ? Enrollment::STATUS_APPROVED : Enrollment::STATUS_PENDING,
                'delivery_mode' => $course->delivery_mode,
                'session' => $data['session'] ?? null,
                'start_date' => $start->toDateString(),
                'end_date' => $end?->toDateString(),
                'duration_weeks' => $weeks ?: null,
                'agreed_fee' => $fees['net_amount'],
                'enrolled_at' => $fees['net_amount'] <= 0 ? now() : null,
            ]);

            // ---- Invoice + Control Number + Installment schedule
            $invoice = Invoice::create([
                'invoice_number' => $this->nextInvoiceNumber(),
                'applicant_id' => $applicant->id,
                'user_id' => $user->id,
                'enrollment_id' => $enrollment->id,
                'course_id' => $course->id,
                'branch_id' => $branchId,
                'total_amount' => $fees['total_amount'],
                'discount_amount' => $fees['discount_amount'],
                'net_amount' => $fees['net_amount'],
                'paid_amount' => 0,
                'balance' => $fees['net_amount'],
                'payment_status' => $fees['net_amount'] <= 0 ? 'paid' : 'unpaid',
                'installment_plan' => $installmentPlan,
                'due_date' => $start->copy()->addDays(7)->toDateString(),
                'issued_at' => now(),
                'created_by' => $actor?->id,
            ]);
            $invoice = $this->srs->ensureControlNumber($invoice);
            $this->srs->buildPaymentSchedules($invoice, $enrollment, $installmentPlan);

            // ---- Optional offline payment captured at the desk / receipt uploaded by applicant
            $payment = null;
            $amount = (float) ($data['amount'] ?? 0);
            if ($receiptPath && $amount <= 0) {
                $amount = $this->firstInstallmentAmount($invoice);
            }
            if ($amount > 0 && ($receiptPath || $source === 'admin') && ($data['payment_option'] ?? 'offline') === 'offline') {
                $payment = $this->srs->applyPaymentToInvoice($invoice, [
                    'amount' => min($amount, (float) $invoice->balance),
                    'payment_method' => $receiptPath ? 'bank_transfer' : 'cash',
                    'installment_type' => $installmentPlan,
                    'receipt_file' => $receiptPath,
                    'receipt_number' => $data['receipt_number'] ?? null,
                    // Registrar-captured payments are verified on the spot; public uploads await verification
                    'verify_immediately' => $source === 'admin',
                    'notes' => $source === 'admin' ? 'Captured at registration desk' : 'Uploaded by applicant during self-application',
                ]);
            }

            return [
                'applicant' => $applicant->fresh(),
                'user' => $user,
                'enrollment' => $enrollment->fresh(),
                'invoice' => $invoice->fresh('paymentSchedules'),
                'payment' => $payment,
                'created_user' => $createdUser,
                'plain_password' => $plainPassword,
            ];
        });
    }

    /**
     * Compute the remaining amount due on the next unpaid installment (or full balance).
     */
    public function firstInstallmentAmount(Invoice $invoice): float
    {
        $next = $invoice->paymentSchedules()->where('status', '!=', 'paid')->orderBy('installment_number')->first();

        return $next ? round((float) $next->amount_due - (float) $next->amount_paid, 2) : (float) $invoice->balance;
    }

    /**
     * Generate a unique institutional invoice number with the configured prefix.
     */
    public function nextInvoiceNumber(): string
    {
        $prefix = generateInvoicePrefix();
        do {
            $candidate = $prefix . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (Invoice::withoutGlobalScope('branch')->where('invoice_number', $candidate)->exists());

        return $candidate;
    }

    /**
     * Parse numeric week duration from a course duration label (e.g., "4 Weeks").
     */
    private function weeksFromLabel(?string $label): int
    {
        if ($label && preg_match('/(\d+)/', $label, $m)) {
            return (int) $m[1];
        }

        return 0;
    }
}
