<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Branch;
use App\Models\Country;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use App\Models\User;
use App\Models\Venue;
use App\Services\SrsWorkflowService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ScmsFullParityAndRbacTest extends TestCase
{
    use DatabaseTransactions;

    private Branch $branchA;

    private Branch $branchB;

    private Venue $venueA;

    private Country $country;

    private Course $f2fCourse;

    private User $superAdmin;

    private User $director;

    private User $branchManager;

    private User $coordinator;

    private User $marketing;

    private User $accountant;

    private User $instructor;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');

        $this->seed(RoleSeeder::class);

        $this->country = Country::firstOrCreate(
            ['code' => 'TZ'],
            ['iso' => 'TZ', 'name' => 'TANZANIA', 'nicename' => 'Tanzania', 'iso3' => 'TZA', 'phonecode' => 255]
        );

        $this->branchA = Branch::create(['name' => 'Dar HQ Test', 'branch_code' => 'DAR-' . Str::random(4), 'is_active' => true]);
        $this->branchB = Branch::create(['name' => 'Arusha Test', 'branch_code' => 'ARU-' . Str::random(4), 'is_active' => true]);
        $this->venueA = Venue::withoutGlobalScope('branch')->create(['branch_id' => $this->branchA->id, 'name' => 'Lab 101', 'capacity' => 30, 'is_active' => true]);

        $this->superAdmin = $this->makeUser('admin', 'super-admin');
        $this->director = $this->makeUser('admin', 'director');
        $this->branchManager = $this->makeUser('admin', 'branch-registrar', $this->branchA->id);
        $this->coordinator = $this->makeUser('admin', 'coordinator', $this->branchA->id);
        $this->marketing = $this->makeUser('admin', 'marketing-officer');
        $this->accountant = $this->makeUser('admin', 'accountant');
        $this->instructor = $this->makeUser('instructor', 'instructor', $this->branchA->id);
        $this->student = $this->makeUser('student', 'student', $this->branchA->id);

        $this->f2fCourse = Course::create([
            'instructor_id' => $this->instructor->id,
            'title' => 'Short Course in Cyber Hygiene',
            'slug' => 'cyber-hygiene-' . Str::random(6),
            'description' => 'Test campus course',
            'regular_price' => 200000,
            'sale_price' => 200000,
            'is_free' => false,
            'status' => 'published',
            'delivery_mode' => 'face_to_face',
            'attendance_threshold' => 75,
        ]);
    }

    private function makeUser(string $role, string $spatieRole, ?int $branchId = null): User
    {
        $user = User::create([
            'name' => Str::title($spatieRole) . ' ' . Str::random(4),
            'email' => Str::lower($spatieRole) . '.' . Str::random(6) . '@out.ac.tz',
            'password' => Hash::make('Str0ng!Pass#1'),
            'role' => $role,
            'branch_id' => $branchId,
            'is_approved' => true,
            'email_verified_at' => now(),
            'password_changed_at' => now(),
        ]);
        $user->syncRoles([$spatieRole]);

        return $user;
    }

    public function test_rbac_enforcement_and_branch_isolation_across_all_scms_roles(): void
    {
        // Branch manager can manage applicants & center reports, NOT staff/settings/audit-logs
        $this->actingAs($this->branchManager)
            ->get(route('admin.campus.applicants.index'))
            ->assertOk();
        $this->actingAs($this->branchManager)
            ->get(route('admin.reports.center-courses'))
            ->assertOk();
        $this->actingAs($this->branchManager)
            ->get(route('admin.staff.index'))
            ->assertStatus(403);
        $this->actingAs($this->branchManager)
            ->get(route('admin.audit-logs.index'))
            ->assertStatus(403);

        // Director can view executive analytics, reports, audit logs, but NOT manage staff or backups
        $this->actingAs($this->director)
            ->get(route('admin.campus.analytics.active-classes'))
            ->assertOk();
        $this->actingAs($this->director)
            ->get(route('admin.audit-logs.index'))
            ->assertOk();
        $this->actingAs($this->director)
            ->get(route('admin.staff.index'))
            ->assertStatus(403);

        // Marketing Officer can view applicant reports & coupons, NOT staff/branches
        $this->actingAs($this->marketing)
            ->get(route('admin.reports.applicants'))
            ->assertOk();
        $this->actingAs($this->marketing)
            ->get(route('admin.staff.index'))
            ->assertStatus(403);

        // Accountant can access invoices & installments, NOT branch manager setup or staff
        $this->actingAs($this->accountant)
            ->get(route('admin.campus.invoices.index'))
            ->assertOk();
        $this->actingAs($this->accountant)
            ->get(route('admin.campus.installments.index'))
            ->assertOk();
        $this->actingAs($this->accountant)
            ->get(route('admin.campus.branches.index'))
            ->assertStatus(403);

        // Branch scoping: branchManager (branchA) must NOT see branchB applicants
        Applicant::withoutGlobalScope('branch')->create([
            'branch_id' => $this->branchB->id,
            'country_id' => $this->country->id,
            'first_name' => 'Secret',
            'last_name' => 'ArushaApplicant',
            'full_name' => 'Secret ArushaApplicant',
            'email' => 'secret.' . Str::random(5) . '@out.ac.tz',
            'phone' => '+255700000111',
        ]);
        $this->actingAs($this->branchManager);
        $visibleNames = Applicant::pluck('full_name')->all();
        $this->assertNotContains('Secret ArushaApplicant', $visibleNames);
    }

    public function test_idle_timeout_strong_password_and_unified_login_redirects(): void
    {
        // Unified login redirects per portal role
        $this->post('/login', ['email' => $this->director->email, 'password' => 'Str0ng!Pass#1'])
            ->assertRedirect(route('admin.dashboard'));
        $this->get('/logout');

        $this->post('/login', ['email' => $this->instructor->email, 'password' => 'Str0ng!Pass#1'])
            ->assertRedirect(route('instructor.dashboard'));
        $this->get('/logout');

        $this->post('/login', ['email' => $this->student->email, 'password' => 'Str0ng!Pass#1'])
            ->assertRedirect(route('student.dashboard'));

        // Idle timeout enforcement (> 5 minutes expired)
        $this->actingAs($this->student)
            ->withSession(['last_activity_ts' => now()->subMinutes(8)->timestamp])
            ->get(route('student.dashboard'))
            ->assertRedirect(route('login'));

        $this->assertTrue(
            Activity::where('log_name', 'security')->where('event', 'auth_session_idle_timeout')->exists()
        );

        // StrongPassword enforcement on force password change
        $this->student->update(['force_password_reset' => true]);
        $this->actingAs($this->student)
            ->post(route('password.force_change.update'), [
                'password' => 'weakpass',
                'password_confirmation' => 'weakpass',
            ])
            ->assertSessionHasErrors('password');

        $this->actingAs($this->student)
            ->post(route('password.force_change.update'), [
                'password' => 'V3ryStr0ng!NewPass',
                'password_confirmation' => 'V3ryStr0ng!NewPass',
            ])
            ->assertRedirect(route('student.dashboard'));
    }

    public function test_reports_analytics_schedules_receipts_search_help_and_portals(): void
    {
        $assignment = CourseAssignment::withoutGlobalScope('branch')->create([
            'course_id' => $this->f2fCourse->id,
            'instructor_id' => $this->instructor->id,
            'branch_id' => $this->branchA->id,
            'venue_id' => $this->venueA->id,
            'session' => 'morning',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addWeeks(2)->toDateString(),
            'status' => 'active',
        ]);

        $applicant = Applicant::withoutGlobalScope('branch')->create([
            'user_id' => $this->student->id,
            'branch_id' => $this->branchA->id,
            'country_id' => $this->country->id,
            'first_name' => 'Amina',
            'last_name' => 'Juma',
            'full_name' => 'Amina Juma',
            'email' => $this->student->email,
            'phone' => '+255712345678',
            'has_discount' => true,
            'discount_reason' => 'Staff',
            'discount_percentage' => 50,
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->f2fCourse->id,
            'applicant_id' => $applicant->id,
            'branch_id' => $this->branchA->id,
            'venue_id' => $this->venueA->id,
            'course_assignment_id' => $assignment->id,
            'delivery_mode' => 'face_to_face',
            'status' => Enrollment::STATUS_APPROVED,
            'amount_paid' => 50000,
        ]);

        $srs = app(SrsWorkflowService::class);
        $invoice = Invoice::withoutGlobalScope('branch')->create([
            'invoice_number' => 'INV-TEST-' . Str::random(6),
            'control_number' => $srs->generateControlNumber(),
            'applicant_id' => $applicant->id,
            'user_id' => $this->student->id,
            'enrollment_id' => $enrollment->id,
            'course_id' => $this->f2fCourse->id,
            'branch_id' => $this->branchA->id,
            'total_amount' => 200000,
            'discount_amount' => 100000,
            'net_amount' => 100000,
            'paid_amount' => 0,
            'balance' => 100000,
            'payment_status' => 'unpaid',
            'installment_plan' => 'half',
            'issued_at' => now(),
        ]);
        $srs->buildPaymentSchedules($invoice, $enrollment, 'half');

        $payment = $srs->applyPaymentToInvoice($invoice, [
            'amount' => 50000,
            'payment_method' => 'bank_transfer',
            'receipt_number' => 'OUT-RCP-777',
            'verify_immediately' => true,
        ]);

        // Reports + Analytics + Global Search + Countries + Audit Logs + Help + Receipts (Admin)
        $this->actingAs($this->superAdmin);
        $this->get(route('admin.reports.center-courses', ['period' => 'monthly']))->assertOk()->assertSee('Dar HQ Test');
        $this->get(route('admin.reports.center-courses', ['period' => 'weekly', 'export' => 'csv']))->assertOk();
        $this->get(route('admin.reports.center-courses', ['period' => 'quarterly', 'export' => 'pdf']))->assertOk();
        $this->get(route('admin.reports.applicants'))->assertOk()->assertSee('Amina Juma');
        $this->get(route('admin.reports.applicants', ['export' => 'excel']))->assertOk();
        $this->get(route('admin.reports.applicants', ['export' => 'pdf']))->assertOk();

        $this->get(route('admin.campus.analytics.active-classes'))->assertOk()->assertSee('Short Course in Cyber Hygiene');
        $this->get(route('admin.campus.analytics.branches'))->assertOk()->assertSee('Dar HQ Test');
        $this->get(route('admin.campus.analytics.discounts'))->assertOk()->assertSee($invoice->invoice_number);
        $this->get(route('admin.campus.analytics.tutor-workload'))->assertOk()->assertSee($this->instructor->name);

        $this->get(route('admin.campus.search', ['q' => 'Amina']))->assertOk()->assertSee('Amina Juma');
        $this->get(route('admin.campus.countries.index'))->assertOk()->assertSee('Tanzania');
        $this->get(route('admin.campus.invoices.receipt', $payment))->assertOk()->assertSee('OUT-RCP-777');
        $this->get(route('admin.help.index'))->assertOk();
        $this->get(route('admin.audit-logs.index'))->assertOk();

        // Generate custom schedule, view, mark-sent, cancel
        $this->post(route('admin.campus.installments.generate'), [
            'invoice_id' => $invoice->id,
            'template' => 'monthly',
            'installments' => 2,
            'start_date' => now()->toDateString(),
        ])->assertRedirect(route('admin.campus.installments.show', $invoice));

        $schedule = PaymentSchedule::where('invoice_id', $invoice->id)->latest('id')->firstOrFail();
        $this->get(route('admin.campus.installments.reminders-list'))->assertOk();
        $this->post(route('admin.campus.installments.mark-sent', $schedule))->assertRedirect();
        $this->post(route('admin.campus.installments.cancel', $schedule))->assertRedirect();
        $this->assertEquals('cancelled', $schedule->fresh()->status);

        // Toggle cohort status
        $this->post(route('admin.campus.timetable.toggle-status', $assignment))->assertRedirect();
        $this->assertEquals('completed', $assignment->fresh()->status);

        // Tutor Roster + Help
        $this->actingAs($this->instructor)
            ->get(route('instructor.campus.roster', [$assignment, 'payment' => 'partial']))
            ->assertOk()
            ->assertSee('Amina Juma');
        $this->get(route('instructor.help.index'))->assertOk();

        // Student Timetable + Receipt + Help
        $this->actingAs($this->student)
            ->get(route('student.campus.timetable'))
            ->assertOk()
            ->assertSee('Short Course in Cyber Hygiene');
        $this->get(route('student.campus.invoices.receipt.show', $payment))
            ->assertOk()
            ->assertSee('OUT-RCP-777');
        $this->get(route('student.help.index'))->assertOk();
    }

    public function test_demo_login_page_lists_all_lsmout_roles_and_enforces_rbac_isolation(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);

        $demoRoles = config('demo_accounts.roles', []);
        $this->assertCount(12, $demoRoles);

        $loginResponse = $this->get(route('login'))->assertOk();
        $loginResponse->assertDontSee('admin@edux.com');
        $loginResponse->assertDontSee('admin@eduex.com');
        $loginResponse->assertDontSee('admin@out.ac.tz');

        foreach ($demoRoles as $roleConfig) {
            $loginResponse->assertSee($roleConfig['email']);
            $loginResponse->assertSee($roleConfig['scope_badge']);
        }

        // Verify all 12 @lsmout.ac.tz demo users authenticate cleanly into their respective portals
        foreach ($demoRoles as $roleConfig) {
            $expectedRedirect = match ($roleConfig['role']) {
                'admin' => route('admin.dashboard'),
                'instructor' => route('instructor.dashboard'),
                default => route('student.dashboard'),
            };

            $this->post(route('login'), [
                'email' => $roleConfig['email'],
                'password' => $roleConfig['password'],
            ])->assertRedirect($expectedRedirect);

            $this->post(route('logout'));
        }

        // Verify RBAC and branch isolation on the seeded @lsmout.ac.tz accounts
        $branchManager = User::where('email', 'branch.manager@lsmout.ac.tz')->firstOrFail();
        $coordinator = User::where('email', 'coordinator@lsmout.ac.tz')->firstOrFail();
        $accountant = User::where('email', 'accountant@lsmout.ac.tz')->firstOrFail();
        $director = User::where('email', 'director@lsmout.ac.tz')->firstOrFail();

        $this->assertNotEquals($branchManager->branch_id, $coordinator->branch_id);

        $this->actingAs($coordinator)->get(route('admin.campus.invoices.index'))->assertStatus(403);
        $this->actingAs($accountant)->get(route('admin.campus.applicants.index'))->assertStatus(403);
        $this->actingAs($director)->get(route('admin.staff.index'))->assertStatus(403);
        $this->actingAs($director)->get(route('admin.settings.index'))->assertStatus(403);
        $this->actingAs($director)->get(route('admin.audit-logs.index'))->assertOk();
    }

    public function test_role_specific_dashboards_dynamic_sidebar_and_model_branch_isolation(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);

        $superAdmin = User::where('email', 'superadmin@lsmout.ac.tz')->firstOrFail();
        $director = User::where('email', 'director@lsmout.ac.tz')->firstOrFail();
        $deputyDirector = User::where('email', 'deputy.director@lsmout.ac.tz')->firstOrFail();
        $marketing = User::where('email', 'marketing@lsmout.ac.tz')->firstOrFail();
        $branchManager = User::where('email', 'branch.manager@lsmout.ac.tz')->firstOrFail();
        $coordinator = User::where('email', 'coordinator@lsmout.ac.tz')->firstOrFail();
        $accountant = User::where('email', 'accountant@lsmout.ac.tz')->firstOrFail();
        $staff = User::where('email', 'staff@lsmout.ac.tz')->firstOrFail();
        $instructor = User::where('email', 'instructor@lsmout.ac.tz')->firstOrFail();
        $student = User::where('email', 'student@lsmout.ac.tz')->firstOrFail();

        // Create an isolated applicant + enrollment + invoice ONLY in Coordinator's branch (not HQ)
        $foreignApplicant = Applicant::withoutGlobalScope('branch')->create([
            'branch_id' => $coordinator->branch_id,
            'country_id' => $this->country->id,
            'first_name' => 'Singida',
            'last_name' => 'OnlyLearner',
            'full_name' => 'Singida OnlyLearner',
            'email' => 'singida.' . Str::random(5) . '@lsmout.ac.tz',
            'phone' => '+255712345678',
        ]);
        $foreignEnrollment = Enrollment::withoutGlobalScope('branch')->create([
            'user_id' => $this->student->id,
            'course_id' => $this->f2fCourse->id,
            'applicant_id' => $foreignApplicant->id,
            'branch_id' => $coordinator->branch_id,
            'delivery_mode' => 'face_to_face',
            'status' => Enrollment::STATUS_APPROVED,
            'enrolled_at' => now(),
        ]);

        // Model-level BranchScoped isolation on Enrollment & Payment
        $this->actingAs($branchManager);
        $this->assertFalse(Enrollment::whereKey($foreignEnrollment->id)->exists());

        // 1. Super Admin dashboard: full global overview + grouped sidebar + top-right user menu
        $this->actingAs($superAdmin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Total Revenue')
            ->assertSee('Top Performing Courses')
            ->assertSee('System Settings')
            ->assertSee('Edit Profile')
            ->assertDontSee('Language Manager');

        // System Settings reusable sub-sidebar renders grouped admin modules
        $this->actingAs($superAdmin)->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Staff &amp; Role Management', false)
            ->assertSee('Frontend &amp; CMS', false)
            ->assertSee('Account Deletions')
            ->assertSee('Language Manager')
            ->assertSee('Backup Management')
            ->assertSee('Online Help');

        $this->actingAs($superAdmin)->get(route('admin.staff.index'))
            ->assertOk()
            ->assertSee('Platform Configuration')
            ->assertSee('Audit &amp; Security Logs', false);

        // 2. Director & Deputy Director: SCMS executive multi-branch oversight
        foreach ([$director, $deputyDirector] as $exec) {
            $this->actingAs($exec)->get(route('admin.dashboard'))
                ->assertOk()
                ->assertSee('Regional Center Performance')
                ->assertSee('Active Scheduled Classes');
        }

        // Director enters System Settings at Audit Logs and sees only permitted sub-sidebar links
        $this->actingAs($director)->get(route('admin.audit-logs.index'))
            ->assertOk()
            ->assertSee('Audit &amp; Security Logs', false)
            ->assertSee('Online Help')
            ->assertDontSee('Staff &amp; Role Management', false)
            ->assertDontSee('Backup Management');

        // 3. Marketing Officer: intake & growth only (no global admin revenue or bank slip queues)
        $this->actingAs($marketing)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Intake Conversion Rate')
            ->assertSee('12-Month Intake &amp; Enrollment Funnel', false)
            ->assertDontSee('Pending Slip Verifications')
            ->assertDontSee('Staff &amp; Role Management', false)
            ->assertDontSee('Total Revenue');

        // 4. Branch Manager: isolated to OUT Headquarters (does not see Singida OnlyLearner)
        $this->actingAs($branchManager)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Branch Revenue Collected')
            ->assertSee('OUT Headquarters')
            ->assertDontSee('Singida OnlyLearner');

        // 5. Coordinator: isolated to Singida Regional Center, academic only (zero financial revenue KPIs)
        $this->actingAs($coordinator)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Center Attendance Rate')
            ->assertSee('Singida Regional Center')
            ->assertDontSee('Branch Collected Revenue')
            ->assertDontSee('Outstanding Receivables');

        // 6. Accountant: isolated to OUT Headquarters, finance & GePG only
        $this->actingAs($accountant)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Outstanding Receivables')
            ->assertSee('Overdue Installments Queue')
            ->assertDontSee('All Instructors')
            ->assertDontSee('Student Engagement');

        // 7. Staff: front desk only (zero financial revenue totals)
        $this->actingAs($staff)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Recent Branch Applicants Desk')
            ->assertSee('Open Support Tickets Queue')
            ->assertDontSee('Branch Collected Revenue')
            ->assertDontSee('Total Revenue');

        // 8. Instructor & Student portals strictly isolated
        $this->actingAs($instructor)->get(route('instructor.dashboard'))->assertOk();
        $this->actingAs($instructor)->get(route('admin.dashboard'))->assertStatus(403);
        $this->actingAs($student)->get(route('student.dashboard'))->assertOk();
        $this->actingAs($student)->get(route('admin.dashboard'))->assertStatus(403);
    }

    public function test_admin_profile_photo_upload_supports_auto_resize_and_removal(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $this->actingAs($this->superAdmin)
            ->get(route('admin.profile.edit'))
            ->assertOk()
            ->assertSee('Auto-Resize Preset')
            ->assertSee('Square 400×400 (Recommended)');

        // Upload 1200x900 photo with square_200 preset -> expect exact 200x200 image
        $largePhoto = \Illuminate\Http\UploadedFile::fake()->image('avatar.jpg', 1200, 900);
        $this->actingAs($this->superAdmin)
            ->put(route('admin.profile.update'), [
                'name' => $this->superAdmin->name,
                'email' => $this->superAdmin->email,
                'phone' => '+255700112233',
                'profile_photo' => $largePhoto,
                'resize_preset' => 'square_200',
            ])
            ->assertRedirect(route('admin.profile.edit'));

        $photoPath = $this->superAdmin->fresh()->profile_photo;
        $this->assertNotNull($photoPath);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($photoPath);

        $dims = getimagesize(\Illuminate\Support\Facades\Storage::disk('public')->path($photoPath));
        $this->assertEquals([200, 200], [$dims[0], $dims[1]]);

        // Upload 1200x600 photo with custom fit 320x240 -> expect aspect-preserved 320x160
        $widePhoto = \Illuminate\Http\UploadedFile::fake()->image('wide.png', 1200, 600);
        $this->actingAs($this->superAdmin)
            ->put(route('admin.profile.update'), [
                'name' => $this->superAdmin->name,
                'email' => $this->superAdmin->email,
                'profile_photo' => $widePhoto,
                'resize_preset' => 'custom',
                'resize_mode' => 'fit',
                'resize_width' => 320,
                'resize_height' => 240,
            ])
            ->assertRedirect(route('admin.profile.edit'));

        $secondPath = $this->superAdmin->fresh()->profile_photo;
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing($photoPath);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($secondPath);

        $dims2 = getimagesize(\Illuminate\Support\Facades\Storage::disk('public')->path($secondPath));
        $this->assertEquals([320, 160], [$dims2[0], $dims2[1]]);

        // Remove photo cleanly
        $this->actingAs($this->superAdmin)
            ->put(route('admin.profile.update'), [
                'name' => $this->superAdmin->name,
                'email' => $this->superAdmin->email,
                'remove_photo' => '1',
            ])
            ->assertRedirect(route('admin.profile.edit'));

        $this->assertNull($this->superAdmin->fresh()->profile_photo);
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing($secondPath);
    }

    public function test_dashboard_supports_filters_card_pagination_and_enforces_branch_isolation(): void
    {
        // Create 7 venues in branchA so default per_page=5 paginates onto page 2
        for ($i = 1; $i <= 7; $i++) {
            Venue::create([
                'branch_id' => $this->branchA->id,
                'name' => sprintf('HQ Seminar Hall %02d', $i),
                'capacity' => 30 + $i,
                'is_active' => true,
            ]);
        }

        Venue::create([
            'branch_id' => $this->branchB->id,
            'name' => 'Zonal Secret Hall',
            'capacity' => 50,
            'is_active' => true,
        ]);

        // Multi-branch admin can filter by center, search, date_range, per_page and navigate card paginators
        $this->actingAs($this->superAdmin)
            ->get(route('admin.dashboard', [
                'branch_id' => $this->branchA->id,
                'per_page' => 5,
                'date_range' => '30d',
            ]))
            ->assertOk()
            ->assertSee('All Centers')
            ->assertSee('Filtered: ' . $this->branchA->name);

        // Director sees paginated branch performance and can filter by search
        $this->actingAs($this->director)
            ->get(route('admin.dashboard', ['search' => $this->branchB->name]))
            ->assertOk()
            ->assertSee($this->branchB->name);

        // Branch Manager sees paginated venues (5 per page, ?venues_page=2) and cannot bypass branch isolation via ?branch_id=
        $this->actingAs($this->branchManager)
            ->get(route('admin.dashboard', ['branch_id' => $this->branchB->id, 'per_page' => 5]))
            ->assertOk()
            ->assertSee('HQ Seminar Hall 01')
            ->assertSee('venues_page=2')
            ->assertDontSee('Zonal Secret Hall')
            ->assertDontSee('name="branch_id"', false);

        $this->actingAs($this->branchManager)
            ->get(route('admin.dashboard', ['per_page' => 5, 'venues_page' => 2]))
            ->assertOk()
            ->assertSee('HQ Seminar Hall 06')
            ->assertDontSee('HQ Seminar Hall 01');
    }

    public function test_student_and_instructor_dashboards_have_clean_grouped_sidebars_filters_and_pagination(): void
    {
        // Ensure 7 instructor courses exist so default per_page=5 paginates onto courses_page=2
        for ($i = 1; $i <= 7; $i++) {
            $course = Course::create([
                'instructor_id' => $this->instructor->id,
                'title' => sprintf('Portal Paginated Course %02d', $i),
                'slug' => 'portal-paginated-course-' . $i . '-' . Str::random(5),
                'status' => 'published',
                'pricing_model' => 'free',
                'delivery_mode' => 'online',
            ]);

            Enrollment::withoutGlobalScope('branch')->create([
                'user_id' => $this->student->id,
                'course_id' => $course->id,
                'branch_id' => $this->branchA->id,
                'delivery_mode' => 'online',
                'status' => Enrollment::STATUS_APPROVED,
                'enrolled_at' => now()->subMinutes(10 - $i),
            ]);
        }

        // Student dashboard: grouped sidebar, filter bar, Continue Learning + Invoices cards & pagination
        $this->actingAs($this->student)
            ->get(route('student.dashboard', ['per_page' => 5]))
            ->assertOk()
            ->assertSee('My Learning')
            ->assertSee('Campus Life')
            ->assertSee('Billing &amp; Fees', false)
            ->assertSee('Support &amp; Tools', false)
            ->assertSee('Invoices &amp; Control Numbers', false)
            ->assertSee('courses_page=2')
            ->assertDontSee('out-nav-group-title');

        // Student search filter narrows results cleanly
        $this->actingAs($this->student)
            ->get(route('student.dashboard', ['search' => 'Course 07']))
            ->assertOk()
            ->assertSee('Portal Paginated Course 07')
            ->assertDontSee('Portal Paginated Course 01');

        // Instructor dashboard: grouped sidebar, filter bar & card pagination
        $this->actingAs($this->instructor)
            ->get(route('instructor.dashboard', ['per_page' => 5]))
            ->assertOk()
            ->assertSee('Courses &amp; Catalog', false)
            ->assertSee('Teaching &amp; Classes', false)
            ->assertSee('Reports &amp; Payouts', false)
            ->assertSee('courses_page=2')
            ->assertDontSee('out-nav-group-title');

        // Instructor page 2 displays remaining courses
        $this->actingAs($this->instructor)
            ->get(route('instructor.dashboard', ['per_page' => 5, 'courses_page' => 2]))
            ->assertOk()
            ->assertSee('Portal Paginated Course');
    }
}
