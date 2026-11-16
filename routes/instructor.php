<?php

/**
 * Instructor Studio Portal Routes
 *
 * Defines guest instructor registration and authenticated instructor routes
 * for course building, question banks, grading, campus rosters, and payouts.
 *
 * @author Renatus Bernardo
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Instructor\RegisterController;
use App\Http\Controllers\Instructor\DashboardController;
use App\Http\Controllers\Instructor\PendingController;
use App\Http\Controllers\Instructor\CoursesController;
use App\Http\Controllers\Instructor\StudentsController;
use App\Http\Controllers\Instructor\AnalyticsController;
use App\Http\Controllers\Instructor\AssignmentsController;
use App\Http\Controllers\Instructor\ReviewsController;
use App\Http\Controllers\Instructor\EarningsController;
use App\Http\Controllers\Instructor\SettingsController;
use App\Http\Controllers\Instructor\CourseBuilderController;
use App\Http\Controllers\Instructor\QuestionBankController;
use App\Http\Controllers\Instructor\QuizAttemptsController;

use App\Http\Controllers\Instructor\ChatController;
use App\Http\Controllers\Instructor\NotificationController;
use App\Http\Controllers\Instructor\BundleController;
use App\Http\Controllers\Instructor\CouponController as InstructorCouponController;
use App\Http\Controllers\Instructor\CourseDiscussionController as InstructorCourseDiscussionController;
use App\Http\Controllers\Instructor\LiveClassController;

// Guest instructor routes
Route::middleware('guest')->group(function () {
    Route::get('/become-instructor', [RegisterController::class, 'showRegistrationForm'])->name('instructor.register');
    Route::post('/become-instructor', [RegisterController::class, 'register'])->name('instructor.register.submit');
});

// Authenticated instructor routes
Route::middleware(['auth', 'role:instructor'])->prefix('instructor')->name('instructor.')->group(function () {
    // Pending instructor routes
    Route::get('/pending', [PendingController::class, 'index'])->name('pending');

    // Dashboard routes
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Courses routes
    Route::get('/courses', [CoursesController::class, 'index'])->name('courses');
    Route::get('/students', [StudentsController::class, 'index'])->name('students');
    Route::get('/students/{id}', [StudentsController::class, 'show'])->name('students.show');

    // Assignments routes
    Route::get('/assignments', [AssignmentsController::class, 'index'])->name('assignments.index');
    Route::get('/assignments/{assignment}', [AssignmentsController::class, 'show'])->name('assignments.show');
    Route::get('/assignments/submissions/{submission}', [AssignmentsController::class, 'showSubmission'])->name('assignments.submissions.show');
    Route::put('/assignments/submissions/{submission}', [AssignmentsController::class, 'updateSubmission'])->name('assignments.submissions.update');

    // Quizzes routes
    Route::get('/quizzes', [QuizAttemptsController::class, 'index'])->name('quizzes.index');
    Route::get('/quizzes/{quiz}', [QuizAttemptsController::class, 'show'])->name('quizzes.show');

    // Analytics routes
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');

    // Reviews routes
    Route::get('/reviews', [ReviewsController::class, 'index'])->name('reviews');
    Route::post('/reviews/{reviewId}/reply', [ReviewsController::class, 'reply'])->name('reviews.reply');
    Route::put('/reviews/{reviewId}/reply/{replyId}', [ReviewsController::class, 'updateReply'])->name('reviews.reply.update');

    // Earnings routes
    Route::get('/earnings', [EarningsController::class, 'index'])->name('earnings');
    Route::post('/earnings/stripe-connect', [EarningsController::class, 'connectStripe'])->name('earnings.stripe-connect');
    Route::get('/withdrawals', [EarningsController::class, 'withdrawals'])->name('withdrawals');
    Route::post('/withdrawals', [EarningsController::class, 'storeWithdrawal'])->name('withdrawals.store');

    // Settings routes
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');
    Route::put('/settings/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications.update');
    Route::delete('/settings/account', [SettingsController::class, 'destroy'])->name('settings.account.destroy');

    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/feed', [NotificationController::class, 'feed'])->name('notifications.feed');
    Route::get('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');
    Route::get('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');

    // Course builder routes
    Route::get('/courses/create', [CourseBuilderController::class, 'create'])->name('courses.create');
    Route::post('/courses', [CourseBuilderController::class, 'store'])->name('courses.store');

    // Question Banks & Merit Lists
    Route::get('/question-banks', [QuestionBankController::class, 'index'])->name('question-banks.index');
    Route::post('/question-banks', [QuestionBankController::class, 'store'])->name('question-banks.store');
    Route::get('/question-banks/{questionBank}', [QuestionBankController::class, 'show'])->name('question-banks.show');
    Route::post('/question-banks/{questionBank}/items', [QuestionBankController::class, 'storeItem'])->name('question-banks.items.store');
    Route::post('/question-banks/{questionBank}/import-csv', [QuestionBankController::class, 'importCsv'])->name('question-banks.import-csv');
    Route::get('/question-banks/{questionBank}/random-paper', [QuestionBankController::class, 'randomPaper'])->name('question-banks.random-paper');
    Route::delete('/question-banks/{questionBank}/items/{item}', [QuestionBankController::class, 'destroyItem'])->name('question-banks.items.destroy');
    Route::delete('/question-banks/{questionBank}', [QuestionBankController::class, 'destroy'])->name('question-banks.destroy');
    Route::get('/quizzes/{quiz}/merit-list', [QuizAttemptsController::class, 'meritList'])->name('quizzes.merit-list');
    Route::post('/submissions/{submission}/originality-check', [App\Http\Controllers\Instructor\AssignmentsController::class, 'checkOriginality'])->name('assignments.submissions.originality');

    // Bundles routes
    Route::resource('bundles', BundleController::class);


    // AI Course Description & Studio
    Route::post('/courses/generate-description', [CourseBuilderController::class, 'generateDescription'])->name('courses.generate-description');
    Route::post('/courses/ai-builder', [CourseBuilderController::class, 'aiCourseBuilder'])->name('courses.ai-builder');
    Route::post('/courses/ai-studio', [CourseBuilderController::class, 'aiStudio'])->name('courses.ai-studio');
    Route::post('/courses/import-youtube-playlist', [CourseBuilderController::class, 'importYoutubePlaylist'])->name('courses.import-youtube-playlist');

    // Course edit routes
    Route::get('/courses/{id}/edit', [CourseBuilderController::class, 'edit'])->name('courses.edit');

    // Course update routes
    Route::put('/courses/{id}', [CourseBuilderController::class, 'update'])->name('courses.update');

    // Chat / Messages routes
    Route::get('messages/start', [ChatController::class, 'startConversation'])->name('messages');
    Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/', [ChatController::class, 'index'])->name('index');
        Route::get('/requests', [ChatController::class, 'requests'])->name('requests');
        Route::get('/unread-count', [ChatController::class, 'unreadCount'])->name('unread-count');
        Route::get('/{conversation}', [ChatController::class, 'show'])->name('show');
        Route::post('/{conversation}/send', [ChatController::class, 'sendMessage'])->name('send');
        Route::get('/{conversation}/poll', [ChatController::class, 'poll'])->name('poll');
        Route::post('/{conversation}/accept', [ChatController::class, 'accept'])->name('accept');
        Route::post('/{conversation}/decline', [ChatController::class, 'decline'])->name('decline');
    });

    // Coupons
    Route::resource('coupons', InstructorCouponController::class);

    // Course Discussions
    Route::prefix('discussions')->name('discussions.')->group(function () {
        Route::get('/', [InstructorCourseDiscussionController::class, 'index'])->name('index');
        Route::post('/course/{courseId}', [InstructorCourseDiscussionController::class, 'store'])->name('store');
        Route::get('/{id}', [InstructorCourseDiscussionController::class, 'show'])->name('show');
        Route::post('/{id}/replies', [InstructorCourseDiscussionController::class, 'storeReply'])->name('replies.store');
        Route::post('/{id}/pin', [InstructorCourseDiscussionController::class, 'togglePin'])->name('pin');
        Route::post('/{id}/announcement', [InstructorCourseDiscussionController::class, 'toggleAnnouncement'])->name('announcement');
        Route::delete('/{id}', [InstructorCourseDiscussionController::class, 'destroy'])->name('destroy');
        Route::delete('/{id}/replies/{replyId}', [InstructorCourseDiscussionController::class, 'destroyReply'])->name('replies.destroy');
    });

    // Live Classes
    Route::get('live-classes/lessons', [LiveClassController::class, 'getLessonsForCourse'])->name('live-classes.lessons');
    Route::post('live-classes/{id}/start', [LiveClassController::class, 'startSession'])->name('live-classes.start');
    Route::post('live-classes/{id}/end', [LiveClassController::class, 'endSession'])->name('live-classes.end');
    Route::resource('live-classes', LiveClassController::class);

    // Support Tickets (v3.0.0)
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Instructor\SupportTicketController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Instructor\SupportTicketController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Instructor\SupportTicketController::class, 'store'])->name('store');
        Route::get('/{ticket}', [\App\Http\Controllers\Instructor\SupportTicketController::class, 'show'])->name('show');
        Route::post('/{ticket}/reply', [\App\Http\Controllers\Instructor\SupportTicketController::class, 'reply'])->name('reply');
        Route::post('/{ticket}/close', [\App\Http\Controllers\Instructor\SupportTicketController::class, 'close'])->name('close');
    });

    // Campus Timetable, Class Rosters & Attendance (F2F / Hybrid)
    Route::prefix('campus')->name('campus.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Instructor\CampusController::class, 'index'])->name('index');
        Route::get('/{assignment}/roster', [\App\Http\Controllers\Instructor\CampusController::class, 'roster'])->name('roster');
        Route::get('/{assignment}/attendance', [\App\Http\Controllers\Instructor\CampusController::class, 'showAttendance'])->name('attendance');
        Route::post('/{assignment}/attendance', [\App\Http\Controllers\Instructor\CampusController::class, 'storeAttendance'])->name('attendance.store');
        Route::post('/{assignment}/qr', [\App\Http\Controllers\Instructor\CampusController::class, 'openQr'])->name('qr');
    });

    Route::get('/help', [\App\Http\Controllers\HelpController::class, 'index'])->name('help.index');
});
