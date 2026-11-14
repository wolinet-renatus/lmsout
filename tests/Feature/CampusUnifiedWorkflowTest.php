<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Country;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentGatewaySetting;
use App\Models\PaymentSchedule;
use App\Models\User;
use App\Models\Venue;
use App\Services\CampusAttendanceService;
use App\Services\PaymentGatewayService;
use App\Services\RevenueShareService;
use App\Services\SettingsRepository;
use App\Services\SrsWorkflowService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class CampusUnifiedWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private User $instructor;

    private User $student;

    private Branch $branch;

    private Venue $venue;

    private Country $country;

    protected function setUp(): void
    {
        parent::setUp();

        URL::forceRootUrl('http://localhost');

        $this->branch = Branch::create([
            'name' => 'Kinondoni Test Center',
            'code' => 'KIN-' . Str::random(4),
            'is_active' => true,
        ]);

        $this->venue = Venue::withoutGlobalScope('branch')->create([
            'branch_id' => $this->branch->id,
            'name' => 'Lab A1',
            'capacity' => 25,
            'is_active' => true,
        ]);

        $this->country = Country::first() ?? Country::create([
            'iso' => 'TZ',
            'name' => 'TANZANIA',
            'nicename' => 'Tanzania',
            'iso3' => 'TZA',
            'phone_code' => '+255',
        ]);

        $this->admin = User::create([
            'name' => 'Test Admin',
            'email' => 'admin-' . Str::random(6) . '@out.ac.tz',
            'password' => Hash::make('Secret@123'),
            'role' => 'admin',
            'is_approved' => true,
            'password_changed_at' => now(),
        ]);

        $this->instructor = User::create([
            'name' => 'Test Instructor',
            'email' => 'inst-' . Str::random(6) . '@out.ac.tz',
            'password' => Hash::make('Secret@123'),
            'role' => 'instructor',
            'is_approved' => true,
            'password_changed_at' => now(),
        ]);

        $this->student = User::create([
            'name' => 'Test Student',
            'email' => 'stud-' . Str::random(6) . '@out.ac.tz',
            'password' => Hash::make('Secret@123'),
            'role' => 'student',
            'is_approved' => true,
            'password_changed_at' => now(),
        ]);
    }

    public function test_online_course_blocks_offline_payment_and_hides_offline_gateway(): void
    {
        Storage::fake('public');

        PaymentGatewaySetting::updateOrCreate(
            ['identifier' => 'offline'],
            ['name' => 'Offline Payment', 'type' => 'offline', 'is_enabled' => true, 'offline_instructions' => 'Bank desk']
        );
        Cache::forget('payment_gateway_settings');

        $onlineCourse = Course::create([
            'instructor_id' => $this->instructor->id,
            'title' => 'Online Python Bootcamp ' . Str::random(4),
            'course_type' => 'course',
            'delivery_mode' => 'online',
            'pricing_model' => 'paid',
            'regular_price' => 150000,
            'visibility' => 'public',
            'status' => 'published',
        ]);

        $viewRes = $this->actingAs($this->student)->get(route('courses.checkout', $onlineCourse->id));
        $viewRes->assertStatus(200);
        $this->assertNull($viewRes->viewData('offlineGateway'));

        $postRes = $this->actingAs($this->student)
            ->from(route('courses.checkout', $onlineCourse->id))
            ->post(route('courses.checkout.process', $onlineCourse->id), [
                'payment_method' => 'offline',
                'transaction_id' => 'TXN-OFFLINE-123',
                'receipt_file' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
            ]);

        $postRes->assertRedirect(route('courses.checkout', $onlineCourse->id));
        $this->assertDatabaseMissing('payments', [
            'user_id' => $this->student->id,
            'transaction_id' => 'TXN-OFFLINE-123',
        ]);
    }

    public function test_face_to_face_redirects_to_scms_applicant_form_and_submits_quarter_installment(): void
    {
        Storage::fake('public');

        PaymentGatewaySetting::updateOrCreate(
            ['identifier' => 'offline'],
            ['name' => 'Control Number / Bank', 'type' => 'offline', 'is_enabled' => true, 'offline_instructions' => 'GePG Control']
        );
        PaymentGatewaySetting::updateOrCreate(
            ['identifier' => 'stripe'],
            ['name' => 'Stripe', 'type' => 'online', 'is_enabled' => true]
        );
        Cache::forget('payment_gateway_settings');

        $f2fCourse = Course::create([
            'instructor_id' => $this->instructor->id,
            'title' => 'Basic Computer Application',
            'course_type' => 'course',
            'delivery_mode' => 'face_to_face',
            'default_branch_id' => $this->branch->id,
            'default_venue_id' => $this->venue->id,
            'duration_label' => '4 weeks',
            'attendance_threshold' => 75,
            'pricing_model' => 'paid',
            'regular_price' => 200000,
            'allow_online_f2f_payment' => true,
            'f2f_commission_enabled' => false,
            'visibility' => 'public',
            'status' => 'published',
        ]);

        // Direct checkout without invoice_id redirects to SCMS Applicant Form
        $this->actingAs($this->student)
            ->get(route('courses.checkout', $f2fCourse->id))
            ->assertRedirect(route('courses.apply', $f2fCourse->id));

        // Fee endpoint flags Basic Computer Application & returns course base fee
        $this->getJson(route('courses.apply.fee', [
            'course' => $f2fCourse->id,
        ]))
            ->assertOk()
            ->assertJsonPath('fee', 200000)
            ->assertJsonPath('is_basic_computer', true);

        // Public application page renders SCMS sections
        $this->get(route('courses.apply', $f2fCourse->id))
            ->assertOk()
            ->assertSeeInOrder([
                'Applicant Information',
                'Staff Student Validation',
                'Course Details',
                'Payment Details',
            ]);

        $applicantEmail = 'applicant-' . Str::random(6) . '@example.tz';
        $response = $this->post(route('courses.apply.store', $f2fCourse->id), [
            'full_name' => 'Asha Juma Mgeni',
            'home_address' => 'Kinondoni Block B, Dar es Salaam',
            'country_id' => $this->country->id,
            'office_of_work' => 'OUT Library',
            'occupation' => 'Assistant',
            'mobile' => '0712345678',
            'email' => $applicantEmail,
            'name_of_sponsor' => 'Self',
            'phone_2' => '0712999888',
            'education_level' => '5',
            'gender' => 'female',
            'is_staff_student' => '1',
            'parent_staff_id' => UploadedFile::fake()->create('staff-id.pdf', 80, 'application/pdf'),
            'course_id' => $f2fCourse->id,
            'branch_id' => $this->branch->id,
            'venue_id' => $this->venue->id,
            'session' => 'morning',
            'start_date' => now()->toDateString(),
            'duration' => '4',
            'installment_type' => 'quarter',
            'apply_discount' => '0',
            'payment_option' => 'offline',
            'receipt' => UploadedFile::fake()->create('slip.jpg', 100, 'image/jpeg'),
            'receipt_number' => 'CRDB-2026-8811',
            'amount' => 25000,
        ]);

        $invoice = Invoice::withoutGlobalScope('branch')->where('course_id', $f2fCourse->id)->latest('id')->first();
        $this->assertNotNull($invoice);
        $response->assertRedirect(route('courses.apply.confirmation', $invoice->id));

        $this->assertStringStartsWith('99104', (string) $invoice->control_number);
        $this->assertEquals(100000.0, (float) $invoice->net_amount);
        // SCMS rule: Staff Student on Basic Computer Application receives 50% fee & forces Full plan
        $this->assertEquals('full', $invoice->installment_plan);

        $schedules = PaymentSchedule::where('invoice_id', $invoice->id)->orderBy('installment_number')->get();
        $this->assertCount(1, $schedules);
        $this->assertEquals(100000.0, round((float) $schedules->sum('amount_due'), 2));

        // Regular applicant submitting Quarter (4x25%) installment + online payment redirect
        \Illuminate\Support\Facades\Auth::logout();
        $regularEmail = 'regular-' . Str::random(6) . '@example.tz';
        $regResponse = $this->post(route('courses.apply.store', $f2fCourse->id), [
            'full_name' => 'Juma Bakari',
            'home_address' => 'Ilala, Dar es Salaam',
            'country_id' => $this->country->id,
            'mobile' => '0754321098',
            'email' => $regularEmail,
            'education_level' => '4',
            'course_id' => $f2fCourse->id,
            'branch_id' => $this->branch->id,
            'venue_id' => $this->venue->id,
            'session' => 'evening',
            'start_date' => now()->toDateString(),
            'duration' => '4',
            'installment_type' => 'quarter',
            'apply_discount' => '0',
            'payment_option' => 'online',
        ]);
        $regInvoice = Invoice::withoutGlobalScope('branch')
            ->where('id', '>', $invoice->id)
            ->where('course_id', $f2fCourse->id)
            ->latest('id')
            ->first();
        $this->assertNotNull($regInvoice);
        $regResponse->assertRedirect(route('courses.checkout', ['id' => $f2fCourse->id, 'invoice_id' => $regInvoice->id]));
        $this->assertEquals('quarter', $regInvoice->installment_plan);
        $this->assertEquals(200000.0, (float) $regInvoice->net_amount);
        $this->assertCount(4, PaymentSchedule::where('invoice_id', $regInvoice->id)->get());

        // Catalog delivery_mode filter returns face_to_face course
        $this->get(route('courses', ['delivery_mode' => 'face_to_face']))
            ->assertOk()
            ->assertSee('Basic Computer Application');

        $payment = Payment::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('pending', $payment->verification_status);
        $this->assertEquals(0.0, (float) $payment->instructor_earning);

        // Public invoice verification endpoint resolves cleanly
        $this->get(route('campus.invoice.verify', $invoice->control_number))
            ->assertOk()
            ->assertSee($invoice->invoice_number)
            ->assertSee($invoice->control_number);

        // Checkout with invoice_id displays BOTH online and offline gateways for F2F course
        $applicantUser = User::where('email', $applicantEmail)->firstOrFail();
        $checkoutF2f = $this->actingAs($applicantUser)->get(route('courses.checkout', [
            'id' => $f2fCourse->id,
            'invoice_id' => $invoice->id,
        ]));
        $checkoutF2f->assertOk();
        $this->assertNotNull($checkoutF2f->viewData('offlineGateway'));
        $this->assertNotNull($checkoutF2f->viewData('campusInvoice'));
    }

    public function test_revenue_share_and_online_payment_sync_with_invoice(): void
    {
        $revenue = app(RevenueShareService::class);
        $srs = app(SrsWorkflowService::class);
        app(SettingsRepository::class)->set('revenue.distribution', [
            'mode' => 'percentage',
            'value' => 30,
        ], 'revenue', 'json');
        Cache::flush();

        $f2fNoComm = Course::create([
            'instructor_id' => $this->instructor->id,
            'title' => 'F2F No Comm ' . Str::random(4),
            'course_type' => 'course',
            'delivery_mode' => 'face_to_face',
            'pricing_model' => 'paid',
            'regular_price' => 100000,
            'f2f_commission_enabled' => false,
            'visibility' => 'public',
            'status' => 'published',
        ]);
        $enrNoComm = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $f2fNoComm->id,
            'branch_id' => $this->branch->id,
            'delivery_mode' => 'face_to_face',
            'status' => Enrollment::STATUS_APPROVED,
        ]);
        $payNoComm = Payment::create([
            'user_id' => $this->student->id,
            'course_id' => $f2fNoComm->id,
            'enrollment_id' => $enrNoComm->id,
            'payment_method' => 'stripe',
            'amount' => 100000,
            'subtotal' => 100000,
            'status' => Payment::STATUS_COMPLETED,
            'transaction_id' => 'no-comm-' . Str::random(8),
        ]);
        $revenue->process($payNoComm, force: true);
        $this->assertEquals(0.0, (float) $payNoComm->instructor_earning);
        $this->assertEquals(100000.0, (float) $payNoComm->platform_commission);

        $f2fWithComm = Course::create([
            'instructor_id' => $this->instructor->id,
            'title' => 'F2F With Comm ' . Str::random(4),
            'course_type' => 'course',
            'delivery_mode' => 'hybrid',
            'pricing_model' => 'paid',
            'regular_price' => 100000,
            'f2f_commission_enabled' => true,
            'visibility' => 'public',
            'status' => 'published',
        ]);
        $enrWithComm = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $f2fWithComm->id,
            'branch_id' => $this->branch->id,
            'delivery_mode' => 'hybrid',
            'status' => Enrollment::STATUS_APPROVED,
        ]);
        $payWithComm = Payment::create([
            'user_id' => $this->student->id,
            'course_id' => $f2fWithComm->id,
            'enrollment_id' => $enrWithComm->id,
            'payment_method' => 'stripe',
            'amount' => 100000,
            'subtotal' => 100000,
            'status' => Payment::STATUS_COMPLETED,
            'transaction_id' => 'with-comm-' . Str::random(8),
        ]);
        $revenue->process($payWithComm, force: true);
        $this->assertEquals(70000.0, (float) $payWithComm->instructor_earning);
        $this->assertEquals(30000.0, (float) $payWithComm->platform_commission);

        // Online payment model hook auto-syncs invoice balance & schedules
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $f2fNoComm->id,
            'branch_id' => $this->branch->id,
            'delivery_mode' => 'face_to_face',
            'status' => Enrollment::STATUS_PENDING,
        ]);

        $invoice = Invoice::withoutGlobalScope('branch')->create([
            'invoice_number' => 'OUT-TEST-' . Str::random(6),
            'control_number' => $srs->generateControlNumber(),
            'control_number_generated_at' => now(),
            'user_id' => $this->student->id,
            'enrollment_id' => $enrollment->id,
            'course_id' => $f2fNoComm->id,
            'branch_id' => $this->branch->id,
            'total_amount' => 100000,
            'discount_amount' => 0,
            'net_amount' => 100000,
            'paid_amount' => 0,
            'balance' => 100000,
            'payment_status' => 'unpaid',
            'installment_plan' => 'half',
            'issued_at' => now()->toDateString(),
        ]);
        $srs->buildPaymentSchedules($invoice, $enrollment, 'half');

        Payment::create([
            'user_id' => $this->student->id,
            'course_id' => $f2fNoComm->id,
            'enrollment_id' => $enrollment->id,
            'invoice_id' => $invoice->id,
            'payment_method' => 'stripe',
            'amount' => 50000,
            'subtotal' => 50000,
            'platform_commission' => 50000,
            'instructor_earning' => 0,
            'commission_processed' => true,
            'status' => Payment::STATUS_COMPLETED,
            'transaction_id' => 'pi_' . Str::random(10),
        ]);

        $invoice->refresh();
        $enrollment->refresh();
        $this->assertEquals(50000.0, (float) $invoice->paid_amount);
        $this->assertEquals(50000.0, (float) $invoice->balance);
        $this->assertEquals('partial', $invoice->payment_status);
        $this->assertEquals(Enrollment::STATUS_APPROVED, $enrollment->status);
    }

    public function test_admin_timetable_conflict_attendance_and_certificate_clearance(): void
    {
        $course = Course::create([
            'instructor_id' => $this->instructor->id,
            'title' => 'Data Analytics F2F ' . Str::random(4),
            'course_type' => 'course',
            'delivery_mode' => 'face_to_face',
            'default_branch_id' => $this->branch->id,
            'default_venue_id' => $this->venue->id,
            'attendance_threshold' => 75,
            'pricing_model' => 'paid',
            'regular_price' => 80000,
            'visibility' => 'public',
            'status' => 'published',
        ]);

        // First timetable booking succeeds
        $this->actingAs($this->admin)->post(route('admin.campus.timetable.store'), [
            'course_id' => $course->id,
            'instructor_id' => $this->instructor->id,
            'branch_id' => $this->branch->id,
            'venue_id' => $this->venue->id,
            'session' => 'morning',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addWeeks(4)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '12:00',
            'status' => 'active',
        ])->assertRedirect(route('admin.campus.timetable.index'));

        $assignment = CourseAssignment::withoutGlobalScope('branch')->where('course_id', $course->id)->firstOrFail();

        // Overlapping booking in same venue/session fails validation
        $this->actingAs($this->admin)
            ->post(route('admin.campus.timetable.store'), [
                'course_id' => $course->id,
                'instructor_id' => $this->instructor->id,
                'branch_id' => $this->branch->id,
                'venue_id' => $this->venue->id,
                'session' => 'morning',
                'start_date' => now()->addDays(2)->toDateString(),
                'end_date' => now()->addWeeks(2)->toDateString(),
                'start_time' => '10:00',
                'end_time' => '11:30',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('venue_id');

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $course->id,
            'course_assignment_id' => $assignment->id,
            'branch_id' => $this->branch->id,
            'venue_id' => $this->venue->id,
            'delivery_mode' => 'face_to_face',
            'session' => 'morning',
            'start_date' => now()->toDateString(),
            'agreed_fee' => 80000,
            'status' => Enrollment::STATUS_APPROVED,
            'enrolled_at' => now(),
        ]);

        $srs = app(SrsWorkflowService::class);
        $invoice = Invoice::withoutGlobalScope('branch')->create([
            'invoice_number' => 'OUT-TEST-' . Str::random(6),
            'control_number' => $srs->generateControlNumber(),
            'control_number_generated_at' => now(),
            'user_id' => $this->student->id,
            'enrollment_id' => $enrollment->id,
            'course_id' => $course->id,
            'branch_id' => $this->branch->id,
            'total_amount' => 80000,
            'discount_amount' => 0,
            'net_amount' => 80000,
            'paid_amount' => 0,
            'balance' => 80000,
            'payment_status' => 'unpaid',
            'installment_plan' => 'full',
            'issued_at' => now()->toDateString(),
        ]);
        $srs->buildPaymentSchedules($invoice, $enrollment, 'full');

        // Save attendance = present -> 100%
        $this->actingAs($this->admin)->post(route('admin.campus.attendance.store', $assignment->id), [
            'date' => now()->toDateString(),
            'marks' => [$enrollment->id => 'present'],
        ])->assertRedirect(route('admin.campus.attendance.show', [$assignment->id, 'date' => now()->toDateString()]));

        $enrollment->refresh();
        $this->assertEquals(100.0, (float) $enrollment->attendance_percentage);
        $this->assertDatabaseHas('attendance_records', [
            'enrollment_id' => $enrollment->id,
            'status' => 'present',
        ]);

        // Certificate blocked while invoice balance > 0
        $this->actingAs($this->admin)->post(route('admin.campus.certificates.update', $enrollment->id), [
            'action' => 'issued',
        ]);
        $enrollment->refresh();
        $this->assertEquals('pending', $enrollment->certificate_status);

        // Settle invoice via Admin recordPayment & issue certificate
        $this->actingAs($this->admin)->post(route('admin.campus.invoices.payments.store', $invoice->id), [
            'amount' => 80000,
            'payment_method' => 'control_number',
            'receipt_number' => 'GEPG-9911',
        ]);

        $this->actingAs($this->admin)->post(route('admin.campus.certificates.update', $enrollment->id), [
            'action' => 'issued',
        ]);

        $enrollment->refresh();
        $this->assertEquals('issued', $enrollment->certificate_status);
        $this->assertStringStartsWith('OUT-CERT-', (string) $enrollment->certificate_serial);

        // Student & Instructor portals render + RBAC check on cross-student receipt upload + QR check-in
        $this->actingAs($this->instructor)->get(route('instructor.campus.index'))->assertOk();
        $this->actingAs($this->student)->get(route('student.campus.invoices.index'))->assertOk();
        $this->actingAs($this->student)->get(route('student.campus.attendance'))->assertOk();

        $token = app(CampusAttendanceService::class)->openQrSession($assignment, 15);
        $this->actingAs($this->student)->get(route('student.campus.checkin', $token))
            ->assertRedirect(route('student.campus.attendance'));

        $otherStudent = User::create([
            'name' => 'Other Student',
            'email' => 'other-' . Str::random(6) . '@out.ac.tz',
            'password' => Hash::make('Secret@123'),
            'role' => 'student',
            'is_approved' => true,
            'password_changed_at' => now(),
        ]);

        $this->actingAs($otherStudent)
            ->post(route('student.campus.invoices.receipt', $invoice->id), [
                'amount' => 1000,
                'receipt_number' => 'HACK-1',
            ])
            ->assertStatus(403);
    }

    public function test_srs_auth_security_lockout_block_and_force_password_reset(): void
    {
        app(SettingsRepository::class)->set('authentication.security', [
            'srs_lockout_enabled' => true,
            'srs_max_attempts' => 2,
            'srs_single_session_enabled' => true,
            'srs_password_expiry_days' => 90,
        ], 'authentication', 'json');
        Cache::flush();

        $target = User::create([
            'name' => 'Locked User',
            'email' => 'lock-' . Str::random(6) . '@out.ac.tz',
            'password' => Hash::make('RightPass@123'),
            'role' => 'student',
            'is_approved' => true,
            'password_changed_at' => now(),
        ]);

        // Two failed attempts trigger lockout
        $this->post(route('login'), ['email' => $target->email, 'password' => 'wrong1']);
        $this->post(route('login'), ['email' => $target->email, 'password' => 'wrong2']);

        $target->refresh();
        $this->assertGreaterThanOrEqual(1, (int) $target->lockout_count);
        $this->assertNotNull($target->locked_until);

        // Admin unblock clears lockout; Admin forceReset enforces redirect to password.force
        $this->actingAs($this->admin)->post(route('admin.user-security.unblock', $target->id));
        $this->actingAs($this->admin)->post(route('admin.user-security.force-reset', $target->id));

        $target->refresh();
        $this->assertNull($target->locked_until);
        $this->assertTrue((bool) $target->force_password_reset);

        // EnforceSrsSecurity redirects forced-reset user to password.force_change
        $this->actingAs($target)
            ->withSession([])
            ->get(route('student.campus.invoices.index'))
            ->assertRedirect(route('password.force_change'));

        // Admin block invalidates access via middleware
        $target->forceFill(['force_password_reset' => false])->save();
        $this->actingAs($this->admin)->post(route('admin.user-security.block', $target->id), ['reason' => 'Policy test']);
        $target->refresh();
        $this->assertTrue((bool) $target->is_blocked);

        $this->actingAs($target)
            ->get(route('student.campus.invoices.index'))
            ->assertRedirect(route('login'));
    }

    public function test_campus_certificate_verification_pdf_and_student_unenroll(): void
    {
        $course = Course::create([
            'instructor_id' => $this->instructor->id,
            'title' => 'Cyber Security Fundamentals ' . Str::random(4),
            'course_type' => 'course',
            'delivery_mode' => 'face_to_face',
            'default_branch_id' => $this->branch->id,
            'default_venue_id' => $this->venue->id,
            'pricing_model' => 'paid',
            'regular_price' => 120000,
            'visibility' => 'public',
            'status' => 'published',
        ]);

        $issuedEnrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $course->id,
            'branch_id' => $this->branch->id,
            'delivery_mode' => 'face_to_face',
            'status' => Enrollment::STATUS_COMPLETED,
            'certificate_status' => 'issued',
            'certificate_serial' => 'OUT-CERT-2026-' . Str::upper(Str::random(6)),
            'certificate_issued_at' => now(),
        ]);

        // Public web verifier supports OUT-CERT-... campus serial
        $this->get(route('verify-certificate', ['code' => $issuedEnrollment->certificate_serial]))
            ->assertOk()
            ->assertSee($issuedEnrollment->certificate_serial)
            ->assertSee($this->branch->name);

        // API verifier supports OUT-CERT-... campus serial
        $this->getJson('/api/verify-certificate/' . $issuedEnrollment->certificate_serial)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.certificate_number', $issuedEnrollment->certificate_serial)
            ->assertJsonPath('data.branch_name', $this->branch->name);

        // Student & Admin can download campus certificate PDF
        $this->actingAs($this->student)
            ->get(route('student.campus.certificates.pdf', $issuedEnrollment->id))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        // Unpaid enrollment can be cancelled from student portal
        $unpaidEnrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $course->id,
            'branch_id' => $this->branch->id,
            'delivery_mode' => 'face_to_face',
            'status' => Enrollment::STATUS_PENDING,
        ]);
        $unpaidInvoice = Invoice::withoutGlobalScope('branch')->create([
            'invoice_number' => 'OUT-UNP-' . Str::random(5),
            'user_id' => $this->student->id,
            'enrollment_id' => $unpaidEnrollment->id,
            'course_id' => $course->id,
            'branch_id' => $this->branch->id,
            'total_amount' => 120000,
            'discount_amount' => 0,
            'net_amount' => 120000,
            'paid_amount' => 0,
            'balance' => 120000,
            'payment_status' => 'unpaid',
            'installment_plan' => 'full',
            'issued_at' => now()->toDateString(),
        ]);

        $this->actingAs($this->student)
            ->delete(route('student.campus.enrollments.destroy', $unpaidEnrollment->id))
            ->assertRedirect();

        $this->assertEquals(Enrollment::STATUS_CANCELLED, $unpaidEnrollment->fresh()->status);
        $this->assertDatabaseMissing('invoices', ['id' => $unpaidInvoice->id]);
    }

    public function test_rest_api_campus_intake_enrollment_guards_attendance_and_srs_auth(): void
    {
        Storage::fake('public');

        PaymentGatewaySetting::updateOrCreate(
            ['identifier' => 'offline'],
            ['name' => 'Offline Payment', 'type' => 'offline', 'is_enabled' => true]
        );
        Cache::flush();

        $onlineCourse = Course::create([
            'instructor_id' => $this->instructor->id,
            'title' => 'API Online Course ' . Str::random(4),
            'course_type' => 'course',
            'delivery_mode' => 'online',
            'pricing_model' => 'paid',
            'regular_price' => 50000,
            'visibility' => 'public',
            'status' => 'published',
        ]);

        $f2fCourse = Course::create([
            'instructor_id' => $this->instructor->id,
            'title' => 'API F2F Course ' . Str::random(4),
            'course_type' => 'course',
            'delivery_mode' => 'face_to_face',
            'default_branch_id' => $this->branch->id,
            'default_venue_id' => $this->venue->id,
            'pricing_model' => 'paid',
            'regular_price' => 160000,
            'visibility' => 'public',
            'status' => 'published',
        ]);

        // Public meta & course discovery filters
        $this->getJson('/api/campus/meta')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/courses?delivery_mode=face_to_face&search=' . urlencode($f2fCourse->title))
            ->assertOk()
            ->assertSee($f2fCourse->title);

        $this->getJson('/api/courses/' . $f2fCourse->id)
            ->assertOk()
            ->assertJsonPath('data.delivery_mode', 'face_to_face')
            ->assertJsonPath('data.requires_applicant_form', true);

        // Online course rejects offline payment in API; F2F rejects direct enroll without invoice_id
        $studentToken = $this->student->createToken('test')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer ' . $studentToken)
            ->postJson('/api/courses/' . $onlineCourse->id . '/enroll', [
                'payment_method' => 'offline',
                'transaction_id' => 'API-OFF-1',
                'receipt_file' => UploadedFile::fake()->create('slip.pdf', 50, 'application/pdf'),
            ])
            ->assertStatus(422);

        $this->withHeader('Authorization', 'Bearer ' . $studentToken)
            ->postJson('/api/courses/' . $f2fCourse->id . '/enroll', [
                'payment_method' => 'stripe',
            ])
            ->assertStatus(422)
            ->assertJsonPath('requires_applicant_form', true);

        // Public API Campus Apply creates student + invoice + control number + schedules
        $apiEmail = 'api-stud-' . Str::random(6) . '@example.tz';
        $applyRes = $this->postJson('/api/campus/courses/' . $f2fCourse->id . '/apply', [
            'full_name' => 'Neema Paul',
            'home_address' => 'Morogoro Centre',
            'country_id' => $this->country->id,
            'mobile' => '0712000111',
            'email' => $apiEmail,
            'education_level' => '5',
            'branch_id' => $this->branch->id,
            'venue_id' => $this->venue->id,
            'session' => 'morning',
            'start_date' => now()->toDateString(),
            'duration' => '4',
            'installment_type' => 'half',
            'payment_option' => 'pay_later',
        ])->assertStatus(201);

        $invoiceId = $applyRes->json('data.invoice.id');
        $invNumber = $applyRes->json('data.invoice.invoice_number');
        $newToken = $applyRes->json('data.token');
        $this->assertNotEmpty($newToken);

        $this->getJson('/api/campus/invoices/verify/' . $invNumber)
            ->assertOk()
            ->assertJsonPath('data.id', $invoiceId);

        // Authenticated API Student endpoints (clear cached sanctum guard user across in-process requests)
        app('auth')->forgetGuards();
        $this->withHeader('Authorization', 'Bearer ' . $newToken)
            ->getJson('/api/campus/invoices')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $invoiceId);

        app('auth')->forgetGuards();
        $this->withHeader('Authorization', 'Bearer ' . $newToken)
            ->postJson('/api/campus/invoices/' . $invoiceId . '/receipt', [
                'amount' => 80000,
                'receipt_number' => 'API-REC-99',
                'receipt' => UploadedFile::fake()->create('bank.jpg', 50, 'image/jpeg'),
            ])
            ->assertStatus(201);

        // Authenticated API Instructor Campus QR + Student API QR Check-In
        $assignment = CourseAssignment::withoutGlobalScope('branch')->create([
            'course_id' => $f2fCourse->id,
            'instructor_id' => $this->instructor->id,
            'branch_id' => $this->branch->id,
            'venue_id' => $this->venue->id,
            'session' => 'morning',
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ]);
        Enrollment::where('id', $applyRes->json('data.enrollment.id'))->update([
            'course_assignment_id' => $assignment->id,
            'status' => Enrollment::STATUS_APPROVED,
        ]);

        app('auth')->forgetGuards();
        $instToken = $this->instructor->createToken('inst')->plainTextToken;
        $qrRes = $this->withHeader('Authorization', 'Bearer ' . $instToken)
            ->postJson('/api/instructor/campus/' . $assignment->id . '/qr')
            ->assertOk();
        $qrToken = $qrRes->json('data.token');

        app('auth')->forgetGuards();
        $this->withHeader('Authorization', 'Bearer ' . $newToken)
            ->postJson('/api/campus/checkin', ['token' => $qrToken])
            ->assertOk()
            ->assertJsonPath('success', true);

        // API Auth enforces SRS lockout & blocking
        app(SettingsRepository::class)->set('authentication.security', [
            'srs_lockout_enabled' => true,
            'srs_max_attempts' => 2,
        ], 'authentication', 'json');
        Cache::flush();

        $apiLocked = User::create([
            'name' => 'API Lock Target',
            'email' => 'apilock-' . Str::random(6) . '@out.ac.tz',
            'password' => Hash::make('Secret@123'),
            'role' => 'student',
            'is_approved' => true,
        ]);
        $this->postJson('/api/auth/login', ['email' => $apiLocked->email, 'password' => 'bad1'])->assertStatus(401);
        $this->postJson('/api/auth/login', ['email' => $apiLocked->email, 'password' => 'bad2'])->assertStatus(401);
        $this->postJson('/api/auth/login', ['email' => $apiLocked->email, 'password' => 'Secret@123'])->assertStatus(423);

        $apiLocked->forceFill(['is_blocked' => true, 'blocked_reason' => 'Suspended'])->save();
        $this->postJson('/api/auth/login', ['email' => $apiLocked->email, 'password' => 'Secret@123'])->assertStatus(403);
    }
}
