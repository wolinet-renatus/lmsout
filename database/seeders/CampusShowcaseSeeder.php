<?php

namespace Database\Seeders;

use App\Models\Applicant;
use App\Models\Branch;
use App\Models\Country;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\PaymentSchedule;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Seeder;

/**
 * Campus showcase seeder — ensures realistic demo campus timetable, applicant,
 * GePG invoice, and installment records exist for live testing after seeding.
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
        $student = User::where('email', 'student@lsmout.ac.tz')->first();
        $instructor = User::where('email', 'instructor@lsmout.ac.tz')->first();
        $admin = User::where('email', 'admin@lsmout.ac.tz')->first();
        $courses = Course::orderBy('id')->take(2)->get();

        if (! $hqBranch || ! $student || ! $instructor || $courses->isEmpty()) {
            return;
        }

        $hqVenue = Venue::where('branch_id', $hqBranch->id)->first();
        $singidaVenue = $singidaBranch ? Venue::where('branch_id', $singidaBranch->id)->first() : null;
        $tanzaniaId = Country::where('iso', 'TZ')->value('id');

        // 1. Ensure primary campus cohort assignment at OUT Headquarters
        $primaryCourse = $courses->first();
        $assignment = CourseAssignment::firstOrCreate(
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

        if ($courses->count() > 1 && $singidaBranch) {
            CourseAssignment::firstOrCreate(
                [
                    'course_id' => $courses[1]->id,
                    'branch_id' => $singidaBranch->id,
                    'session' => 'Evening',
                ],
                [
                    'instructor_id' => $instructor->id,
                    'venue_id' => $singidaVenue?->id,
                    'start_date' => now()->addDays(3)->toDateString(),
                    'end_date' => now()->addWeeks(10)->toDateString(),
                    'schedule_days' => ['Tue', 'Thu'],
                    'start_time' => '17:00:00',
                    'end_time' => '19:30:00',
                    'max_capacity' => 25,
                    'status' => 'active',
                ]
            );
        }

        // 2. Ensure demo student has an Applicant record at OUT HQ
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

        // 3. Link demo student's enrollment to course_assignment & issue an official GePG Invoice
        $enrollment = Enrollment::where('user_id', $student->id)
            ->where('course_id', $primaryCourse->id)
            ->first();

        if ($enrollment && empty($enrollment->course_assignment_id)) {
            $enrollment->update([
                'branch_id' => $hqBranch->id,
                'applicant_id' => $applicant->id,
                'venue_id' => $hqVenue?->id,
                'course_assignment_id' => $assignment->id,
                'delivery_mode' => 'hybrid',
                'session' => 'Morning',
                'start_date' => $assignment->start_date,
                'end_date' => $assignment->end_date,
                'agreed_fee' => 79.00,
            ]);
        }

        $invoice = Invoice::firstOrCreate(
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
                'invoice_id' => $invoice->id,
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
    }
}
