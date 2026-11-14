<?php

namespace App\Console\Commands;

use App\Models\Applicant;
use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class ImportScmsCommand extends Command
{
    protected $signature = 'scms:import {--dry-run : Preview source counts and mappings without modifying OUT LMS database}';

    protected $description = 'Idempotently import SCMS branches, venues, users, courses, applicants, enrollments, invoices, schedules, payments, and certificates into OUT LMS';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $src = DB::connection('scms');

        try {
            $src->getPdo();
        } catch (\Throwable $e) {
            $this->error('Could not connect to [scms] database: ' . $e->getMessage());
            return self::FAILURE;
        }

        $summary = [
            'branches' => $src->table('branches')->count(),
            'venues' => $src->table('venues')->count(),
            'users' => $src->table('users')->count(),
            'tutors' => $src->table('tutors')->count(),
            'courses' => $src->table('courses')->count(),
            'applicants' => $src->table('applicants')->count(),
            'enrollments' => $src->table('enrollments')->count(),
            'invoices' => $src->table('invoices')->whereNull('deleted_at')->count(),
            'payment_schedules' => $src->table('payment_schedules')->count(),
            'payments' => $src->table('payments')->whereNull('deleted_at')->count(),
            'course_assignments' => $src->table('course_assignments')->count(),
            'attendance_records' => $src->table('attendance_records')->count(),
            'certificates' => $src->table('certificates')->count(),
            'source_invoiced_total' => (float) $src->table('invoices')->whereNull('deleted_at')->sum('total_amount'),
            'source_paid_total' => (float) $src->table('payments')->whereNull('deleted_at')->where('payment_status', 'paid')->sum('amount'),
        ];

        $this->table(['Entity / Metric', 'SCMS Source Count'], collect($summary)->map(fn ($v, $k) => [$k, is_float($v) ? number_format($v, 2) : $v])->values()->all());

        if ($dryRun) {
            $this->info('Dry-run complete. No rows were written to OUT LMS.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($src) {
            $branchMap = [];
            foreach ($src->table('branches')->orderBy('id')->get() as $b) {
                $row = Branch::updateOrCreate(
                    ['legacy_scms_id' => $b->id],
                    [
                        'name' => $b->name,
                        'address' => trim(implode(', ', array_filter([$b->location ?? null, $b->region ?? null]))) ?: null,
                        'is_active' => (bool) ($b->is_active ?? true),
                    ]
                );
                $branchMap[$b->id] = $row->id;
            }

            $venueMap = [];
            foreach ($src->table('venues')->orderBy('id')->get() as $v) {
                $branchId = $branchMap[$v->branch_id] ?? null;
                if (! $branchId) {
                    continue;
                }
                $row = Venue::withoutGlobalScope('branch')->updateOrCreate(
                    ['legacy_scms_id' => $v->id],
                    [
                        'branch_id' => $branchId,
                        'name' => $v->name,
                        'capacity' => 30,
                        'is_active' => true,
                    ]
                );
                $venueMap[$v->id] = $row->id;
            }

            // Map davinci roles + users
            $scmsRoles = $src->table('davinci_roles')->pluck('name', 'id')->all();
            $roleMap = [
                'system-admin'         => ['admin', 'system-administrator'],
                'branch-admin'         => ['admin', 'branch-registrar'],
                'training-coordinator' => ['admin', 'coordinator'],
                'marketing-officer'    => ['admin', 'marketing-officer'],
                'director'             => ['admin', 'director'],
                'tutor'                => ['instructor', 'instructor'],
                'student'              => ['student', 'student'],
            ];

            $userMap = [];
            foreach ($src->table('users')->orderBy('id')->get() as $u) {
                $scmsRole = $scmsRoles[$u->davinci_role_id] ?? ($u->user_type === 'staff' ? 'branch-admin' : 'student');
                [$coreRole, $spatieRole] = $roleMap[$scmsRole] ?? ['student', 'student'];
                $email = Str::lower(trim((string) $u->email));

                $existing = User::where('legacy_scms_id', $u->id)->orWhere('email', $email)->first();
                $payload = [
                    'name' => $u->name,
                    'email' => $email,
                    'phone' => $u->phone,
                    'address' => $u->address,
                    'branch_id' => $u->branch_id ? ($branchMap[$u->branch_id] ?? null) : null,
                    'is_blocked' => (bool) ($u->is_blocked ?? false),
                    'blocked_at' => $u->blocked_at,
                    'blocked_reason' => $u->blocked_reason,
                    'failed_login_attempts' => (int) ($u->failed_login_attempts ?? 0),
                    'lockout_count' => (int) ($u->lockout_count ?? 0),
                    'locked_until' => $u->locked_until,
                    'force_password_reset' => (bool) ($u->force_password_reset ?? false),
                    'password_changed_at' => $u->password_changed_at,
                    'legacy_scms_id' => $u->id,
                ];

                if ($existing) {
                    $existing->update($payload);
                    $target = $existing;
                } else {
                    $target = User::create(array_merge($payload, [
                        'password' => $u->password ?: Hash::make(Str::random(16)),
                        'role' => $coreRole,
                        'is_approved' => true,
                        'email_verified_at' => $u->email_verified_at ?? now(),
                    ]));
                }
                if (Role::where('name', $spatieRole)->exists()) {
                    $target->assignRole($spatieRole);
                }
                $userMap[$u->id] = $target->id;
            }

            // Map tutors into instructor users
            $tutorUserMap = [];
            foreach ($src->table('tutors')->orderBy('id')->get() as $t) {
                $uId = $t->user_id ? ($userMap[$t->user_id] ?? null) : null;
                if (! $uId && ! empty($t->email)) {
                    $tutor = User::firstOrCreate(
                        ['email' => Str::lower(trim($t->email))],
                        [
                            'name' => $t->name,
                            'password' => Hash::make(Str::random(16)),
                            'role' => 'instructor',
                            'phone' => $t->phone,
                            'address' => $t->address,
                            'branch_id' => $t->branch_id ? ($branchMap[$t->branch_id] ?? null) : null,
                            'is_approved' => true,
                        ]
                    );
                    if (Role::where('name', 'instructor')->exists()) {
                        $tutor->assignRole('instructor');
                    }
                    $uId = $tutor->id;
                }
                if ($uId) {
                    $tutorUserMap[$t->id] = $uId;
                }
            }

            $defaultOwnerId = User::where('role', 'admin')->value('id') ?? User::value('id');

            $courseMap = [];
            foreach ($src->table('courses')->orderBy('id')->get() as $c) {
                $mode = match (strtolower((string) ($c->teaching_method ?? 'face_to_face'))) {
                    'online' => 'online',
                    'hybrid', 'blended' => 'hybrid',
                    default => 'face_to_face',
                };
                $row = Course::updateOrCreate(
                    ['legacy_scms_id' => $c->id],
                    [
                        'instructor_id' => $defaultOwnerId,
                        'title' => $c->title,
                        'course_type' => 'course',
                        'delivery_mode' => $mode,
                        'pricing_model' => ((float) ($c->fee ?? 0) > 0) ? 'paid' : 'free',
                        'regular_price' => (float) ($c->fee ?? 0),
                        'has_discount' => (bool) ($c->has_discount ?? false),
                        'discount_amount' => (float) ($c->discount ?? 0),
                        'duration_label' => $c->duration ? ($c->duration . ' weeks') : null,
                        'category' => $c->category,
                        'visibility' => 'public',
                        'status' => ($c->is_active ?? true) ? 'published' : 'archived',
                    ]
                );
                $courseMap[$c->id] = $row->id;
            }

            $applicantMap = [];
            foreach ($src->table('applicants')->orderBy('id')->get() as $a) {
                $branchId = $a->branch_id ? ($branchMap[$a->branch_id] ?? null) : null;
                $userId = $a->user_id ? ($userMap[$a->user_id] ?? null) : null;
                $row = Applicant::withoutGlobalScope('branch')->updateOrCreate(
                    ['legacy_scms_id' => $a->id],
                    [
                        'user_id' => $userId,
                        'branch_id' => $branchId,
                        'country_id' => $a->country_id ?: null,
                        'full_name' => $a->full_name,
                        'home_address' => $a->home_address,
                        'office_of_work' => $a->office_of_work,
                        'occupation' => $a->occupation,
                        'phone' => $a->mobile ?? '',
                        'email' => $a->email,
                        'name_of_sponsor' => $a->name_of_sponsor,
                        'sponsor_phone' => $a->phone_2,
                        'education_level' => $a->education_level,
                        'is_staff_student' => (bool) ($a->is_staff_student ?? false),
                        'parent_staff_id_path' => $a->parent_staff_id,
                        'has_discount' => (bool) ($a->is_staff_family_member || $a->is_staff || $a->is_out_student),
                    ]
                );
                $applicantMap[$a->id] = ['id' => $row->id, 'user_id' => $userId, 'branch_id' => $branchId];
            }

            $assignmentMap = [];
            foreach ($src->table('course_assignments')->orderBy('id')->get() as $ca) {
                if (empty($courseMap[$ca->course_id])) {
                    continue;
                }
                $row = CourseAssignment::withoutGlobalScope('branch')->updateOrCreate(
                    ['legacy_scms_id' => $ca->id],
                    [
                        'course_id' => $courseMap[$ca->course_id],
                        'instructor_id' => $ca->tutor_id ? ($tutorUserMap[$ca->tutor_id] ?? null) : null,
                        'branch_id' => $ca->branch_id ? ($branchMap[$ca->branch_id] ?? null) : null,
                        'venue_id' => $ca->venue_id ? ($venueMap[$ca->venue_id] ?? null) : null,
                        'session' => 'morning',
                        'start_date' => $ca->date ?? now()->toDateString(),
                        'start_time' => $ca->start_time ? substr($ca->start_time, 0, 5) : null,
                        'end_time' => $ca->end_time ? substr($ca->end_time, 0, 5) : null,
                        'status' => ($ca->active ?? true) ? 'active' : 'completed',
                    ]
                );
                $assignmentMap[$ca->id] = $row->id;
            }

            $enrollmentMap = [];
            foreach ($src->table('enrollments')->orderBy('id')->get() as $e) {
                $app = $applicantMap[$e->applicant_id] ?? null;
                $courseId = $courseMap[$e->course_id] ?? null;
                $userId = $app['user_id'] ?? $defaultOwnerId;
                if (! $courseId || ! $userId) {
                    continue;
                }
                $venueId = $e->venue_id ? ($venueMap[$e->venue_id] ?? null) : null;
                $branchId = $app['branch_id'] ?? ($venueId ? Venue::withoutGlobalScope('branch')->where('id', $venueId)->value('branch_id') : null);
                $status = match (strtolower((string) ($e->enrollment_status ?? 'active'))) {
                    'completed' => Enrollment::STATUS_COMPLETED,
                    'cancelled' => Enrollment::STATUS_CANCELLED,
                    'pending' => Enrollment::STATUS_PENDING,
                    default => Enrollment::STATUS_APPROVED,
                };
                $row = Enrollment::updateOrCreate(
                    ['legacy_scms_id' => $e->id],
                    [
                        'user_id' => $userId,
                        'course_id' => $courseId,
                        'applicant_id' => $app['id'] ?? null,
                        'branch_id' => $branchId,
                        'venue_id' => $venueId,
                        'delivery_mode' => Course::where('id', $courseId)->value('delivery_mode') ?? 'face_to_face',
                        'session' => strtolower((string) ($e->session ?? 'morning')),
                        'start_date' => $e->start_date,
                        'end_date' => $e->end_date,
                        'duration_weeks' => is_numeric($e->duration) ? (int) $e->duration : null,
                        'agreed_fee' => (float) ($e->fee ?? 0),
                        'attendance_percentage' => (float) ($e->attendance_percentage ?? 0),
                        'status' => $status,
                        'enrolled_at' => $e->created_at,
                    ]
                );
                $enrollmentMap[$e->id] = $row->id;
            }

            $invoiceMap = [];
            foreach ($src->table('invoices')->whereNull('deleted_at')->orderBy('id')->get() as $i) {
                $app = $applicantMap[$i->applicant_id] ?? null;
                $enrId = $i->enrollment_id ? ($enrollmentMap[$i->enrollment_id] ?? null) : null;
                $enr = $enrId ? Enrollment::find($enrId) : null;
                $invNumber = trim((string) ($i->invoice_prefix ?? 'SCMS-')) . str_pad((string) $i->id, 5, '0', STR_PAD_LEFT);
                $total = (float) ($i->total_amount ?? 0);
                $disc = (float) ($i->discount_amount ?? 0);
                $net = max(0, $total - $disc);
                $paid = (float) ($i->paid_amount ?? 0);
                $bal = (float) ($i->balance ?? max(0, $net - $paid));
                $pStatus = $bal <= 0.01 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');

                $row = Invoice::withoutGlobalScope('branch')->updateOrCreate(
                    ['legacy_scms_id' => $i->id],
                    [
                        'invoice_number' => $invNumber,
                        'control_number' => $i->control_number ?: null,
                        'control_number_generated_at' => $i->control_number_generated_at,
                        'applicant_id' => $app['id'] ?? null,
                        'user_id' => $app['user_id'] ?? $enr?->user_id,
                        'enrollment_id' => $enrId,
                        'course_id' => $enr?->course_id,
                        'branch_id' => $app['branch_id'] ?? $enr?->branch_id,
                        'total_amount' => $total,
                        'discount_amount' => $disc,
                        'net_amount' => $net,
                        'paid_amount' => $paid,
                        'balance' => $bal,
                        'payment_status' => $pStatus,
                        'installment_plan' => 'full',
                        'due_date' => $i->due_date,
                        'issued_at' => $i->date ?? $i->created_at,
                        'notes' => $i->description,
                    ]
                );
                $invoiceMap[$i->id] = $row->id;
            }

            foreach ($src->table('payment_schedules')->orderBy('id')->get() as $ps) {
                $invId = $invoiceMap[$ps->invoice_id] ?? null;
                if (! $invId) {
                    continue;
                }
                PaymentSchedule::updateOrCreate(
                    ['legacy_scms_id' => $ps->id],
                    [
                        'invoice_id' => $invId,
                        'enrollment_id' => Invoice::withoutGlobalScope('branch')->where('id', $invId)->value('enrollment_id'),
                        'installment_number' => (int) ($ps->installment_number ?? 1),
                        'installment_label' => 'Installment #' . ($ps->installment_number ?? 1),
                        'amount_due' => (float) ($ps->amount ?? 0),
                        'amount_paid' => ($ps->status === 'paid') ? (float) ($ps->amount ?? 0) : 0,
                        'due_date' => $ps->due_date ?? now()->toDateString(),
                        'status' => $ps->status ?? 'pending',
                        'paid_at' => $ps->paid_at,
                        'reminder_sent_at' => $ps->sent_at,
                    ]
                );
            }

            Payment::withoutEvents(function () use ($src, $invoiceMap, $applicantMap, $userMap) {
                foreach ($src->table('payments')->whereNull('deleted_at')->orderBy('id')->get() as $p) {
                    $invId = $p->invoice_id ? ($invoiceMap[$p->invoice_id] ?? null) : null;
                    $inv = $invId ? Invoice::withoutGlobalScope('branch')->find($invId) : null;
                    $app = $p->applicant_id ? ($applicantMap[$p->applicant_id] ?? null) : null;
                    $amt = (float) ($p->amount ?? 0);
                    $status = ($p->payment_status === 'paid' || $p->verification_status === 'verified') ? Payment::STATUS_COMPLETED : Payment::STATUS_PENDING;

                    Payment::updateOrCreate(
                        ['legacy_scms_id' => $p->id],
                        [
                            'user_id' => ($p->user_id ? ($userMap[$p->user_id] ?? null) : null) ?? $inv?->user_id,
                            'enrollment_id' => $inv?->enrollment_id,
                            'invoice_id' => $invId,
                            'applicant_id' => $app['id'] ?? $inv?->applicant_id,
                            'payment_method' => $p->payment_method ?: 'cash',
                            'installment_type' => $p->installment_type ?: 'full',
                            'amount' => $amt,
                            'subtotal' => $amt,
                            'platform_commission' => $amt,
                            'instructor_earning' => 0,
                            'commission_processed' => true,
                            'status' => $status,
                            'verification_status' => $p->verification_status ?: ($status === Payment::STATUS_COMPLETED ? 'verified' : 'pending'),
                            'receipt_file' => $p->receipt_file ?: $p->receipt,
                            'receipt_number' => $p->receipt_number,
                            'payment_date' => $p->date ? substr((string) $p->date, 0, 10) : null,
                            'transaction_id' => $p->transaction_reference,
                        ]
                    );
                }
            });

            foreach ($src->table('attendance_records')->orderBy('id')->get() as $ar) {
                $enrId = $enrollmentMap[$ar->enrollment_id] ?? null;
                $enr = $enrId ? Enrollment::find($enrId) : null;
                if (! $enr) {
                    continue;
                }
                AttendanceRecord::withoutGlobalScope('branch')->updateOrCreate(
                    ['legacy_scms_id' => $ar->id],
                    [
                        'enrollment_id' => $enr->id,
                        'course_id' => $enr->course_id,
                        'course_assignment_id' => $ar->course_assignment_id ? ($assignmentMap[$ar->course_assignment_id] ?? null) : null,
                        'user_id' => $enr->user_id,
                        'branch_id' => $enr->branch_id,
                        'attendance_date' => $ar->marked_at ? substr((string) $ar->marked_at, 0, 10) : now()->toDateString(),
                        'status' => $ar->status ?? 'present',
                        'notes' => $ar->notes,
                    ]
                );
            }

            foreach ($src->table('certificates')->orderBy('id')->get() as $cert) {
                $enrId = $enrollmentMap[$cert->enrollment_id] ?? null;
                if (! $enrId) {
                    continue;
                }
                Enrollment::where('id', $enrId)->update([
                    'certificate_serial' => $cert->serial_number,
                    'certificate_status' => $cert->status ?? 'pending',
                    'certificate_ready_at' => $cert->ready_at,
                    'certificate_issued_at' => $cert->issued_at,
                    'certificate_issued_by' => $cert->issued_by ? ($userMap[$cert->issued_by] ?? null) : null,
                ]);
            }
        });

        $this->info('SCMS data imported cleanly!');
        $this->table(['Unified Table', 'OUT LMS Row Count'], [
            ['branches', Branch::count()],
            ['venues', Venue::withoutGlobalScope('branch')->count()],
            ['applicants', Applicant::withoutGlobalScope('branch')->count()],
            ['enrollments (F2F/Hybrid)', Enrollment::whereIn('delivery_mode', ['face_to_face', 'hybrid'])->count()],
            ['invoices', Invoice::withoutGlobalScope('branch')->count()],
            ['payment_schedules', PaymentSchedule::count()],
            ['payments (with invoice)', Payment::whereNotNull('invoice_id')->count()],
        ]);

        return self::SUCCESS;
    }
}
