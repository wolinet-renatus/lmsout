<?php

namespace Database\Seeders;

use App\Models\Applicant;
use App\Models\Banner;
use App\Models\Branch;
use App\Models\CertificateTemplate;
use App\Models\Country;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\CourseCategory;
use App\Models\CourseLanguage;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\PaymentSchedule;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Seeder;

/**
 * Campus showcase seeder — seeds all institutional OUT Face-to-Face short courses,
 * homepage banner, and synthetic campus cohort/invoice records for live testing
 * without exposing private personal data in version control.
 *
 * @author Renatus Bernardo
 */
class CampusShowcaseSeeder extends Seeder
{
    /**
     * Execute the database seeder.
     */
    public function run(): void
    {
        $hqBranch = Branch::where('name', 'OUT Headquarters')->first() ?? Branch::orderBy('id')->first();
        $singidaBranch = Branch::where('name', 'Singida Regional Center')->first() ?? $hqBranch;
        $mwanzaBranch = Branch::where('name', 'Mwanza Regional Center')->first() ?? $hqBranch;
        $student = User::where('email', 'student@lsmout.ac.tz')->first();
        $instructor = User::where('email', 'instructor@lsmout.ac.tz')->first();
        $admin = User::where('email', 'admin@lsmout.ac.tz')->first();

        // 1. Default homepage hero banner (only if none exists)
        if (Banner::count() === 0) {
            Banner::create([
                'title' => 'Master Full-Stack & Applied AI Engineering',
                'subtitle' => 'Join 50,000+ learners building real-world projects with expert mentorship.',
                'image_path' => 'courses/images/out-campus-ict.jpg',
                'link' => '/courses',
                'button_text' => 'Explore Courses',
                'order' => 1,
                'is_active' => true,
            ]);
        }

        if (! $hqBranch || ! $instructor) {
            return;
        }

        $hqVenue = Venue::where('branch_id', $hqBranch->id)->first();
        $singidaVenue = $singidaBranch ? Venue::where('branch_id', $singidaBranch->id)->first() : null;
        $mwanzaVenue = $mwanzaBranch ? Venue::where('branch_id', $mwanzaBranch->id)->first() : null;
        $tanzaniaId = Country::where('iso', 'TZ')->value('id');
        $defaultLang = CourseLanguage::where('is_active', true)->first();
        $defaultCert = CertificateTemplate::where('is_default', true)->first() ?? CertificateTemplate::first();
        $catWeb = CourseCategory::where('slug', 'web-development')->first() ?? CourseCategory::first();
        $catData = CourseCategory::where('slug', 'data-science-ai')->first() ?? $catWeb;
        $catDesign = CourseCategory::where('slug', 'ui-ux-product-design')->first() ?? $catWeb;
        $catMarketing = CourseCategory::where('slug', 'digital-marketing')->first() ?? $catWeb;
        $catBusiness = CourseCategory::where('slug', 'business-strategy')->first() ?? $catWeb;
        $catCloud = CourseCategory::where('slug', 'cloud-cybersecurity')->first() ?? $catWeb;

        // 2. Seed all 16 institutional OUT Face-to-Face Short Courses (TZS)
        $shortCourses = [
            ['scms_id' => 1, 'title' => 'Basic computer Application', 'duration' => '5 weeks', 'fee' => 172000.00, 'cat' => $catWeb, 'img' => 'courses/images/out-computer-apps.jpg'],
            ['scms_id' => 2, 'title' => 'Video Production and Motion Graphics Design', 'duration' => '6 weeks', 'fee' => 450000.00, 'cat' => $catDesign, 'img' => 'courses/images/out-video-production.jpg'],
            ['scms_id' => 3, 'title' => 'Advanced Computer Applications', 'duration' => '5 weeks', 'fee' => 250000.00, 'cat' => $catWeb, 'img' => 'courses/images/out-campus-ict.jpg'],
            ['scms_id' => 4, 'title' => 'Graphic Design', 'duration' => '5 weeks', 'fee' => 350000.00, 'cat' => $catDesign, 'img' => 'courses/images/out-uiux-design.jpg'],
            ['scms_id' => 5, 'title' => 'Computer Maintenance and Repair', 'duration' => '8 weeks', 'fee' => 390000.00, 'cat' => $catCloud, 'img' => 'courses/images/out-hardware-repair.jpg'],
            ['scms_id' => 6, 'title' => 'Data  Analysis with SPSS', 'duration' => '5 weeks', 'fee' => 300000.00, 'cat' => $catData, 'img' => 'courses/images/out-ai-data-science.jpg'],
            ['scms_id' => 7, 'title' => 'Advanced Website Development', 'duration' => '8 weeks', 'fee' => 800000.00, 'cat' => $catWeb, 'img' => 'courses/images/out-web-development.jpg'],
            ['scms_id' => 8, 'title' => 'Basic Website Development', 'duration' => '4 weeks', 'fee' => 400000.00, 'cat' => $catWeb, 'img' => 'courses/images/out-web-development.jpg'],
            ['scms_id' => 9, 'title' => 'Advanced  Microsoft  project', 'duration' => '5 weeks', 'fee' => 400000.00, 'cat' => $catBusiness, 'img' => 'courses/images/out-business-leadership.jpg'],
            ['scms_id' => 10, 'title' => 'Social Media and Digital Marketing Strategies', 'duration' => '5 weeks', 'fee' => 400000.00, 'cat' => $catMarketing, 'img' => 'courses/images/out-digital-marketing.jpg'],
            ['scms_id' => 11, 'title' => 'IT Essentials (I&II)', 'duration' => '8 weeks', 'fee' => 390000.00, 'cat' => $catCloud, 'img' => 'courses/images/out-hardware-repair.jpg'],
            ['scms_id' => 12, 'title' => 'Get connect', 'duration' => '5 weeks', 'fee' => 90000.00, 'cat' => $catWeb, 'img' => 'courses/images/out-computer-apps.jpg'],
            ['scms_id' => 13, 'title' => 'CCNA (Non Student)', 'duration' => '8 weeks', 'fee' => 800000.00, 'cat' => $catCloud, 'img' => 'courses/images/out-cloud-networking.jpg'],
            ['scms_id' => 14, 'title' => 'CCNA (Enrolled Student)', 'duration' => '8 weeks', 'fee' => 400000.00, 'cat' => $catCloud, 'img' => 'courses/images/out-cloud-networking.jpg'],
            ['scms_id' => 15, 'title' => 'E-learning and Instructional Design', 'duration' => '6 weeks', 'fee' => 450000.00, 'cat' => $catDesign, 'img' => 'courses/images/out-campus-ict.jpg'],
            ['scms_id' => 16, 'title' => 'DATABASE TECHNOLOGY', 'duration' => '8 weeks', 'fee' => 400000.00, 'cat' => $catData, 'img' => 'courses/images/out-ai-data-science.jpg'],
        ];

        $createdShortCourses = [];
        foreach ($shortCourses as $sc) {
            $course = Course::updateOrCreate(
                ['legacy_scms_id' => $sc['scms_id']],
                [
                    'instructor_id' => $instructor->id,
                    'certificate_template_id' => $defaultCert?->id,
                    'title' => $sc['title'],
                    'course_type' => 'campus',
                    'delivery_mode' => 'face_to_face',
                    'default_branch_id' => $hqBranch->id,
                    'default_venue_id' => $hqVenue?->id,
                    'duration_label' => $sc['duration'],
                    'description' => sprintf(
                        'Official Open University of Tanzania (OUT) hands-on short course: %s (%s). Delivered in regional training labs with continuous assessment and GePG-verified certification.',
                        trim(preg_replace('/\s+/', ' ', $sc['title'])),
                        $sc['duration']
                    ),
                    'featured_image' => $sc['img'],
                    'visibility' => 'public',
                    'public_course' => true,
                    'max_students' => 30,
                    'difficulty' => 'beginner',
                    'pricing_model' => 'paid',
                    'regular_price' => $sc['fee'],
                    'sale_price' => null,
                    'price_currency' => 'TZS',
                    'has_discount' => false,
                    'course_category_id' => $sc['cat']?->id,
                    'course_language_id' => $defaultLang?->id,
                    'category' => $sc['cat']?->name ?? 'Development',
                    'language' => $defaultLang?->name ?? 'English',
                    'status' => 'published',
                    'attendance_threshold' => 75,
                    'allow_online_f2f_payment' => true,
                    'f2f_commission_enabled' => false,
                ]
            );
            $createdShortCourses[] = $course;
        }

        // 3. Seed sample campus cohorts across branches
        $onlineCourses = Course::where('delivery_mode', 'online')->orderBy('id')->take(2)->get();
        $primaryCourse = $onlineCourses->first() ?? ($createdShortCourses[0] ?? null);
        $f2fCourse = $createdShortCourses[0] ?? $primaryCourse;
        $ccnaCourse = $createdShortCourses[12] ?? $f2fCourse;

        if (! $primaryCourse) {
            return;
        }

        $hqAssignment = CourseAssignment::firstOrCreate(
            [
                'course_id' => $primaryCourse->id,
                'branch_id' => $hqBranch->id,
                'session' => 'Morning',
            ],
            [
                'instructor_id' => $instructor->id,
                'venue_id' => $hqVenue?->id,
                'start_date' => now()->subDays(5)->toDateString(),
                'end_date' => now()->addWeeks(8)->toDateString(),
                'schedule_days' => ['Mon', 'Wed', 'Fri'],
                'start_time' => '09:00:00',
                'end_time' => '12:00:00',
                'max_capacity' => 30,
                'status' => 'active',
            ]
        );

        if ($f2fCourse) {
            CourseAssignment::firstOrCreate(
                [
                    'course_id' => $f2fCourse->id,
                    'branch_id' => $hqBranch->id,
                    'session' => 'Afternoon',
                ],
                [
                    'instructor_id' => $instructor->id,
                    'venue_id' => $hqVenue?->id,
                    'start_date' => now()->addDays(2)->toDateString(),
                    'end_date' => now()->addWeeks(5)->toDateString(),
                    'schedule_days' => ['Tue', 'Thu'],
                    'start_time' => '14:00:00',
                    'end_time' => '16:30:00',
                    'max_capacity' => 30,
                    'status' => 'active',
                ]
            );
        }

        if ($ccnaCourse && $singidaBranch) {
            CourseAssignment::firstOrCreate(
                [
                    'course_id' => $ccnaCourse->id,
                    'branch_id' => $singidaBranch->id,
                    'session' => 'Evening',
                ],
                [
                    'instructor_id' => $instructor->id,
                    'venue_id' => $singidaVenue?->id,
                    'start_date' => now()->addDays(4)->toDateString(),
                    'end_date' => now()->addWeeks(8)->toDateString(),
                    'schedule_days' => ['Mon', 'Wed', 'Fri'],
                    'start_time' => '17:00:00',
                    'end_time' => '19:30:00',
                    'max_capacity' => 25,
                    'status' => 'active',
                ]
            );
        }

        if ($mwanzaBranch && count($createdShortCourses) > 5) {
            CourseAssignment::firstOrCreate(
                [
                    'course_id' => $createdShortCourses[5]->id,
                    'branch_id' => $mwanzaBranch->id,
                    'session' => 'Weekend',
                ],
                [
                    'instructor_id' => $instructor->id,
                    'venue_id' => $mwanzaVenue?->id,
                    'start_date' => now()->addDays(7)->toDateString(),
                    'end_date' => now()->addWeeks(6)->toDateString(),
                    'schedule_days' => ['Sat'],
                    'start_time' => '09:00:00',
                    'end_time' => '13:00:00',
                    'max_capacity' => 30,
                    'status' => 'active',
                ]
            );
        }

        if (! $student) {
            return;
        }

        // 4. Demo student Applicant + PAID + PENDING GePG Invoices for immediate portal testing
        $applicant = Applicant::firstOrCreate(
            ['user_id' => $student->id],
            [
                'branch_id' => $hqBranch->id,
                'country_id' => $tanzaniaId,
                'first_name' => 'Alex',
                'last_name' => 'Rivera',
                'full_name' => $student->name,
                'email' => $student->email,
                'phone' => '+255 712 345 678',
                'gender' => 'Male',
                'education_level' => 'Bachelor Degree',
                'address' => 'Dar es Salaam, Tanzania',
                'source' => 'online',
            ]
        );

        $enrollment = Enrollment::where('user_id', $student->id)
            ->where('course_id', $primaryCourse->id)
            ->first();

        if ($enrollment && empty($enrollment->course_assignment_id)) {
            $enrollment->update([
                'branch_id' => $hqBranch->id,
                'applicant_id' => $applicant->id,
                'venue_id' => $hqVenue?->id,
                'course_assignment_id' => $hqAssignment->id,
                'delivery_mode' => 'hybrid',
                'session' => 'Morning',
                'start_date' => $hqAssignment->start_date,
                'end_date' => $hqAssignment->end_date,
                'agreed_fee' => 79.00,
            ]);
        }

        $paidInvoice = Invoice::firstOrCreate(
            ['invoice_number' => 'INV-OUT-2026-0001'],
            [
                'control_number' => '991260000001',
                'control_number_generated_at' => now()->subDays(10),
                'applicant_id' => $applicant->id,
                'user_id' => $student->id,
                'enrollment_id' => $enrollment?->id,
                'course_id' => $primaryCourse->id,
                'branch_id' => $hqBranch->id,
                'total_amount' => 79.00,
                'discount_amount' => 0.00,
                'net_amount' => 79.00,
                'paid_amount' => 79.00,
                'balance' => 0.00,
                'payment_status' => 'paid',
                'installment_plan' => 'full',
                'due_date' => now()->subDays(3)->toDateString(),
                'issued_at' => now()->subDays(10),
                'paid_at' => now()->subDays(9),
                'notes' => 'Seeded paid GePG campus demonstration invoice.',
                'created_by' => $admin?->id,
            ]
        );

        PaymentSchedule::firstOrCreate(
            [
                'invoice_id' => $paidInvoice->id,
                'installment_number' => 1,
            ],
            [
                'enrollment_id' => $enrollment?->id,
                'installment_label' => 'Full Tuition Settlement',
                'amount_due' => 79.00,
                'amount_paid' => 79.00,
                'due_date' => now()->subDays(3)->toDateString(),
                'status' => 'paid',
                'paid_at' => now()->subDays(9),
            ]
        );

        // Seed a second sample unpaid face-to-face invoice for Pesapal / GePG payment flow testing
        if ($f2fCourse) {
            $pendingInvoice = Invoice::firstOrCreate(
                ['invoice_number' => 'INV-OUT-2026-0002'],
                [
                    'control_number' => '991260000002',
                    'control_number_generated_at' => now()->subDay(),
                    'applicant_id' => $applicant->id,
                    'user_id' => $student->id,
                    'course_id' => $f2fCourse->id,
                    'branch_id' => $hqBranch->id,
                    'total_amount' => (float) $f2fCourse->regular_price,
                    'discount_amount' => 0.00,
                    'net_amount' => (float) $f2fCourse->regular_price,
                    'paid_amount' => 0.00,
                    'balance' => (float) $f2fCourse->regular_price,
                    'payment_status' => 'unpaid',
                    'installment_plan' => 'half',
                    'due_date' => now()->addDays(14)->toDateString(),
                    'issued_at' => now()->subDay(),
                    'notes' => 'Seeded pending short-course tuition invoice ready for Pesapal/GePG testing.',
                    'created_by' => $admin?->id,
                ]
            );

            $half = round(((float) $f2fCourse->regular_price) / 2, 2);
            PaymentSchedule::firstOrCreate(
                ['invoice_id' => $pendingInvoice->id, 'installment_number' => 1],
                [
                    'installment_label' => '1st Installment (50%)',
                    'amount_due' => $half,
                    'amount_paid' => 0.00,
                    'due_date' => now()->addDays(7)->toDateString(),
                    'status' => 'pending',
                ]
            );
            PaymentSchedule::firstOrCreate(
                ['invoice_id' => $pendingInvoice->id, 'installment_number' => 2],
                [
                    'installment_label' => 'Final Installment (50%)',
                    'amount_due' => round(((float) $f2fCourse->regular_price) - $half, 2),
                    'amount_paid' => 0.00,
                    'due_date' => now()->addDays(21)->toDateString(),
                    'status' => 'pending',
                ]
            );
        }
    }
}
