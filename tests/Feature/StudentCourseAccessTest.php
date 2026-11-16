<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\CourseTopic;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * End-to-end verification of the Student Course Player, progression locking,
 * navigation, quiz/assignment workflows, discussions, and certificate routes.
 *
 * @author Renatus Bernardo
 */
class StudentCourseAccessTest extends TestCase
{
    use DatabaseTransactions;

    private User $instructor;

    private User $student;

    private Course $course;

    private Enrollment $enrollment;

    private Lesson $lesson1;

    private Quiz $quiz1;

    private QuizQuestion $question1;

    private Assignment $assignment1;

    protected function setUp(): void
    {
        parent::setUp();

        URL::forceRootUrl('http://localhost');

        $this->instructor = User::create([
            'name'                => 'Course Access Instructor',
            'email'               => 'ca-inst-' . Str::random(6) . '@out.ac.tz',
            'password'            => Hash::make('Secret@123'),
            'role'                => 'instructor',
            'is_approved'         => true,
            'password_changed_at' => now(),
        ]);

        $this->student = User::create([
            'name'                => 'Course Access Student',
            'email'               => 'ca-stud-' . Str::random(6) . '@out.ac.tz',
            'password'            => Hash::make('Secret@123'),
            'role'                => 'student',
            'is_approved'         => true,
            'password_changed_at' => now(),
        ]);

        $this->course = Course::create([
            'title'         => 'Software Architecture Practicum ' . Str::random(4),
            'course_type'   => 'course',
            'delivery_mode' => 'online',
            'description'   => 'Course player integration test course',
            'instructor_id' => $this->instructor->id,
            'pricing_model' => 'free',
            'status'        => 'published',
            'visibility'    => 'public',
        ]);

        $topic = CourseTopic::create([
            'course_id' => $this->course->id,
            'title'     => 'Module 1: Foundations',
            'order'     => 1,
        ]);

        $this->lesson1 = Lesson::create([
            'course_topic_id' => $topic->id,
            'title'           => 'Lesson 1: Domain Driven Design',
            'description'     => 'Introductory lecture on bounded contexts.',
            'video_type'      => 'url',
            'video_url'       => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'duration'        => 600,
            'order'           => 1,
        ]);

        $this->quiz1 = Quiz::create([
            'course_topic_id' => $topic->id,
            'title'           => 'Quiz 1: Bounded Context Check',
            'passing_score'   => 70,
            'time_limit'      => 15,
            'order'           => 2,
        ]);

        $this->question1 = QuizQuestion::create([
            'quiz_id'        => $this->quiz1->id,
            'question'       => 'What defines a bounded context?',
            'type'           => 'multiple-choice',
            'options'        => ['Explicit model boundary', 'Global shared DB', 'Random folder'],
            'correct_answer' => 0,
            'order'          => 1,
        ]);

        $this->assignment1 = Assignment::create([
            'course_topic_id' => $topic->id,
            'title'           => 'Assignment 1: Context Map',
            'description'     => 'Upload or describe your domain context map.',
            'order'           => 3,
        ]);

        $this->enrollment = Enrollment::create([
            'user_id'      => $this->student->id,
            'course_id'    => $this->course->id,
            'status'       => Enrollment::STATUS_APPROVED,
            'requested_at' => now(),
            'approved_at'  => now(),
        ]);
    }

    public function test_course_access_loads_first_lesson_and_renders_youtube_toolbar_cleanly(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('student.courses.access', $this->course->id));

        $response->assertStatus(200);
        $response->assertSee($this->lesson1->title, false);
        $response->assertSee('0/3 (0%)', false);
        $response->assertSee('YouTube Player', false);
        $response->assertSee('Theater Mode', false);
        $response->assertDontSee('lmsoutVideoSeek(-10)', false);
        $response->assertDontSee('disabled style=&quot;opacity: 0.6', false);

        // Initial visit initializes LessonProgress automatically
        $this->assertDatabaseHas('lesson_progress', [
            'user_id'       => $this->student->id,
            'lesson_id'     => $this->lesson1->id,
            'enrollment_id' => $this->enrollment->id,
            'is_completed'  => false,
        ]);
    }

    public function test_mark_complete_works_without_prior_lesson_load_and_unlocks_next_item(): void
    {
        // Before completion, Next returns locked reason and Previous returns start reason
        $lockedNext = $this->actingAs($this->student)
            ->getJson(route('student.courses.item.next', [
                'courseId' => $this->course->id,
                'itemId'   => $this->lesson1->id,
                'type'     => 'lesson',
            ]));

        $lockedNext->assertStatus(200)
            ->assertJson([
                'success' => false,
                'reason'  => 'locked',
            ]);

        $startPrev = $this->actingAs($this->student)
            ->getJson(route('student.courses.item.previous', [
                'courseId' => $this->course->id,
                'itemId'   => $this->lesson1->id,
                'type'     => 'lesson',
            ]));

        $startPrev->assertStatus(200)
            ->assertJson([
                'success' => false,
                'reason'  => 'start',
            ]);

        // Directly call markComplete even if no lesson_progress row exists yet
        $completeRes = $this->actingAs($this->student)
            ->postJson(route('student.courses.lesson.complete', $this->course->id), [
                'lesson_id' => $this->lesson1->id,
                'course_id' => $this->course->id,
            ]);

        $completeRes->assertStatus(200)
            ->assertJson([
                'success'          => true,
                'course_completed' => false,
                'next_item'        => [
                    'item_id' => $this->quiz1->id,
                    'type'    => 'quiz',
                ],
                'navigation'       => [
                    'has_previous'    => false,
                    'has_next'        => true,
                    'next_accessible' => true,
                ],
            ]);

        // Re-visiting /access automatically resumes on Quiz 1
        $resumeRes = $this->actingAs($this->student)
            ->get(route('student.courses.access', $this->course->id));
        $resumeRes->assertStatus(200);
        $resumeRes->assertSee($this->quiz1->title, false);
        $resumeRes->assertSee('1/3 (33%)', false);

        // Explicit item query still loads Lesson 1 with clean unescaped disabled attribute
        $explicitLessonRes = $this->actingAs($this->student)
            ->get(route('student.courses.access', [
                'courseId'  => $this->course->id,
                'item_type' => 'lesson',
                'item_id'   => $this->lesson1->id,
            ]));
        $explicitLessonRes->assertStatus(200);
        $explicitLessonRes->assertSee('disabled style="opacity: 0.6; cursor: not-allowed;"', false);
    }

    public function test_full_progression_quiz_retake_assignment_submission_and_certificate_download(): void
    {
        LessonProgress::create([
            'user_id'       => $this->student->id,
            'lesson_id'     => $this->lesson1->id,
            'enrollment_id' => $this->enrollment->id,
            'is_completed'  => true,
            'completed_at'  => now(),
        ]);

        // Empty auto-submit when timer expires returns 200 rather than 422
        $emptyQuizRes = $this->actingAs($this->student)
            ->postJson(route('student.courses.quiz.submit', [
                'courseId' => $this->course->id,
                'quizId'   => $this->quiz1->id,
            ]), [
                'quiz_id'    => $this->quiz1->id,
                'answers'    => [],
                'time_taken' => 15,
            ]);
        $emptyQuizRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'passed'  => false,
            ]);

        // Pass Quiz 1 -> unlocks Assignment 1
        $passQuizRes = $this->actingAs($this->student)
            ->postJson(route('student.courses.quiz.submit', [
                'courseId' => $this->course->id,
                'quizId'   => $this->quiz1->id,
            ]), [
                'quiz_id' => $this->quiz1->id,
                'answers' => [
                    [
                        'question_id'     => $this->question1->id,
                        'selected_answer' => 0,
                    ],
                ],
                'time_taken' => 2,
            ]);
        $passQuizRes->assertStatus(200)
            ->assertJson([
                'success'          => true,
                'passed'           => true,
                'course_completed' => false,
                'next_item'        => [
                    'item_id' => $this->assignment1->id,
                    'type'    => 'assignment',
                ],
                'progress_summary' => [
                    'total_items'     => 3,
                    'completed_items' => 2,
                    'percentage'      => 67,
                ],
            ]);

        // Refresh sidebar preserves passed state and highlights active item
        $sidebarRes = $this->actingAs($this->student)
            ->getJson(route('student.courses.sidebar.refresh', [
                'courseId'    => $this->course->id,
                'active_type' => 'quiz',
                'active_id'   => $this->quiz1->id,
            ]));
        $sidebarRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'accessibleItems' => [
                    'lesson_' . $this->lesson1->id         => true,
                    'quiz_' . $this->quiz1->id             => true,
                    'assignment_' . $this->assignment1->id => true,
                ],
                'progress_summary' => [
                    'completed_items' => 2,
                    'total_items'     => 3,
                ],
            ]);

        // End of curriculum check on Assignment 1
        $endNextRes = $this->actingAs($this->student)
            ->getJson(route('student.courses.item.next', [
                'courseId' => $this->course->id,
                'itemId'   => $this->assignment1->id,
                'type'     => 'assignment',
            ]));
        $endNextRes->assertStatus(200)
            ->assertJson([
                'success' => false,
                'reason'  => 'end',
            ]);

        // Submit Assignment 1 -> completes course
        $assignRes = $this->actingAs($this->student)
            ->postJson(route('student.courses.assignment.submit', [
                'courseId'     => $this->course->id,
                'assignmentId' => $this->assignment1->id,
            ]), [
                'assignment_id'   => $this->assignment1->id,
                'submission_text' => 'My complete domain context map architecture.',
            ]);
        $assignRes->assertStatus(200)
            ->assertJson([
                'success'          => true,
                'course_completed' => true,
                'progress_summary' => [
                    'completed_items' => 3,
                    'total_items'     => 3,
                    'percentage'      => 100,
                ],
            ]);

        $submission = $this->student->assignmentSubmissions()
            ->where('assignment_id', $this->assignment1->id)
            ->firstOrFail();

        // Two-parameter route (courseId, submissionId) must resolve cleanly
        $viewSubRes = $this->actingAs($this->student)
            ->getJson(route('student.courses.assignment.submission', [
                'courseId'     => $this->course->id,
                'submissionId' => $submission->id,
            ]));
        $viewSubRes->assertStatus(200)
            ->assertJson(['success' => true]);

        // Search endpoint
        $searchRes = $this->actingAs($this->student)
            ->postJson(route('student.courses.search', $this->course->id), [
                'q' => 'Bounded',
            ]);
        $searchRes->assertStatus(200)
            ->assertJson(['success' => true]);
        $this->assertNotEmpty($searchRes->json('results'));

        // Discussions endpoint
        $forumRes = $this->actingAs($this->student)
            ->getJson(route('student.courses.discussions.index', $this->course->id));
        $forumRes->assertStatus(200)
            ->assertJson(['success' => true]);

        // Completion page
        $completionRes = $this->actingAs($this->student)
            ->get(route('student.courses.completion', $this->course->id));
        $completionRes->assertStatus(200);
    }

    public function test_load_endpoints_return_navigation_without_500_and_paid_course_unlocks_all_modules(): void
    {
        $paidCourse = Course::create([
            'title'         => 'Paid Multi-Module Architecture Course ' . Str::random(4),
            'course_type'   => 'course',
            'delivery_mode' => 'online',
            'description'   => 'Paid full unlock test course',
            'instructor_id' => $this->instructor->id,
            'pricing_model' => 'paid',
            'regular_price' => 122500,
            'status'        => 'published',
            'visibility'    => 'public',
        ]);

        $mod1 = CourseTopic::create([
            'course_id' => $paidCourse->id,
            'title'     => 'Module 1: Intro',
            'order'     => 1,
        ]);
        $mod2 = CourseTopic::create([
            'course_id' => $paidCourse->id,
            'title'     => 'Module 2: Advanced Capstone',
            'order'     => 2,
        ]);

        $m1Lesson = Lesson::create([
            'course_topic_id' => $mod1->id,
            'title'           => 'Module 1 Lesson',
            'video_type'      => 'url',
            'video_url'       => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'duration'        => 300,
            'order'           => 1,
        ]);
        $m1Quiz = Quiz::create([
            'course_topic_id' => $mod1->id,
            'title'           => 'Module 1 Quiz',
            'passing_score'   => 70,
            'time_limit'      => 10,
            'order'           => 2,
        ]);
        $m2Lesson = Lesson::create([
            'course_topic_id' => $mod2->id,
            'title'           => 'Module 2 Lesson',
            'video_type'      => 'url',
            'video_url'       => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'duration'        => 420,
            'order'           => 1,
        ]);
        $m2Assignment = Assignment::create([
            'course_topic_id' => $mod2->id,
            'title'           => 'Module 2 Assignment',
            'description'     => 'Submit capstone.',
            'order'           => 2,
        ]);

        $paidEnrollment = Enrollment::create([
            'user_id'      => $this->student->id,
            'course_id'    => $paidCourse->id,
            'status'       => Enrollment::STATUS_APPROVED,
            'requested_at' => now(),
            'approved_at'  => now(),
        ]);

        $payment = \App\Models\Payment::create([
            'user_id'             => $this->student->id,
            'enrollment_id'       => $paidEnrollment->id,
            'payment_method'      => \App\Models\Payment::PAYMENT_METHOD_PESAPAL,
            'amount'              => 122500,
            'status'              => \App\Models\Payment::STATUS_COMPLETED,
            'verification_status' => 'verified',
        ]);
        $paidEnrollment->update(['payment_id' => $payment->id]);

        // Initial page renders all Module 1 & Module 2 items unlocked even before completing Lesson 1
        $accessRes = $this->actingAs($this->student)
            ->get(route('student.courses.access', $paidCourse->id));
        $accessRes->assertOk()
            ->assertSee($m2Lesson->title, false)
            ->assertSee($m2Assignment->title, false)
            ->assertDontSee('fa-lock status-locked', false);

        // Next button from Module 1 Lesson advances cleanly without being blocked as locked
        $nextRes = $this->actingAs($this->student)
            ->getJson(route('student.courses.item.next', [
                'courseId' => $paidCourse->id,
                'itemId'   => $m1Lesson->id,
                'type'     => 'lesson',
            ]));
        $nextRes->assertOk()
            ->assertJson([
                'success' => true,
                'item_id' => $m1Quiz->id,
                'type'    => 'quiz',
            ]);

        // POST lesson/load, quiz/load, and assignment/load return 200 with navigation state (never 500 search() on null)
        $this->actingAs($this->student)
            ->postJson(route('student.courses.lesson.load', $paidCourse->id), [
                'course_id' => $paidCourse->id,
                'lesson_id' => $m2Lesson->id,
            ])
            ->assertOk()
            ->assertJson([
                'success'   => true,
                'lesson_id' => $m2Lesson->id,
                'type'      => 'lesson',
                'navigation' => [
                    'has_previous'    => true,
                    'has_next'        => true,
                    'next_accessible' => true,
                ],
            ]);

        $this->actingAs($this->student)
            ->postJson(route('student.courses.quiz.load', [
                'courseId' => $paidCourse->id,
                'quizId'   => $m1Quiz->id,
            ]), ['course_id' => $paidCourse->id])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'quiz_id' => $m1Quiz->id,
                'type'    => 'quiz',
                'navigation' => [
                    'has_previous'    => true,
                    'has_next'        => true,
                    'next_accessible' => true,
                ],
            ]);

        $this->actingAs($this->student)
            ->postJson(route('student.courses.assignment.load', [
                'courseId'     => $paidCourse->id,
                'assignmentId' => $m2Assignment->id,
            ]), ['course_id' => $paidCourse->id])
            ->assertOk()
            ->assertJson([
                'success'       => true,
                'assignment_id' => $m2Assignment->id,
                'type'          => 'assignment',
                'navigation' => [
                    'has_previous'    => true,
                    'has_next'        => false,
                    'next_accessible' => false,
                ],
            ]);

        // Installment / partial balance course keeps later items sequentially locked until balance is settled
        $paidInvoice = \App\Models\Invoice::withoutGlobalScope('branch')->findOrFail($payment->fresh()->invoice_id);
        $paidInvoice->update([
            'payment_status' => 'partial',
            'balance'        => 50000,
        ]);

        $this->assertSame('partial', $paidInvoice->fresh()->payment_status);
        $this->assertSame(Enrollment::STATUS_APPROVED, $paidEnrollment->fresh()->status);
        $this->assertDatabaseMissing('lesson_progress', [
            'enrollment_id' => $paidEnrollment->id,
            'lesson_id'     => $m1Lesson->id,
            'is_completed'  => true,
        ]);

        $sidebarCheck = $this->actingAs($this->student)
            ->getJson(route('student.courses.sidebar.refresh', ['courseId' => $paidCourse->id]));
        $sidebarCheck->assertOk()->assertJsonPath('accessibleItems.quiz_' . $m1Quiz->id, false);

        $partialNextRes = $this->actingAs($this->student)
            ->getJson(route('student.courses.item.next', [
                'courseId' => $paidCourse->id,
                'itemId'   => $m1Lesson->id,
                'type'     => 'lesson',
            ]));
        $partialNextRes->assertOk()
            ->assertJson([
                'success' => false,
                'reason'  => 'locked',
            ]);
    }
}
