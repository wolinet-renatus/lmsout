<?php

/**
 * Admin Control Center Routes
 *
 * Defines RBAC-protected administrative routes for dashboard analytics,
 * catalog management, campus operations, finance/billing, CMS, and backups.
 *
 * @author Renatus Bernardo
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AssignmentSubmissionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InstructorController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Instructor\CourseBuilderController as SharedCourseBuilderController;
use App\Http\Controllers\Admin\BundleController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\AccountDeletionController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\PaymentGatewayController;
use App\Http\Controllers\Admin\CourseCategoryController;
use App\Http\Controllers\Admin\CourseLanguageController;
use App\Http\Controllers\Admin\FrontendSettingController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\QuizAttemptController;
use App\Http\Controllers\Admin\WithdrawalController;
use App\Http\Controllers\Admin\CertificateController;
use App\Http\Controllers\Admin\CertificateTemplateController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\SupportTicketController as AdminSupportTicketController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\SystemMaintenanceController;
use App\Http\Controllers\Admin\NewsletterController as AdminNewsletterController;
use App\Http\Controllers\Admin\CustomPageController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\CouponController;

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Settings Management
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('/settings/platform', [SettingsController::class, 'platform'])->name('settings.platform');
    Route::put('/settings/platform', [SettingsController::class, 'updatePlatform'])->name('settings.platform.update');
    Route::get('/settings/ai', [SettingsController::class, 'ai'])->name('settings.ai');
    Route::put('/settings/ai', [SettingsController::class, 'updateAi'])->name('settings.ai.update');
    Route::get('/settings/email-notifications', [SettingsController::class, 'emailNotifications'])->name('settings.email');
    Route::put('/settings/email-notifications', [SettingsController::class, 'updateEmailNotifications'])->name('settings.email.update');
    Route::get('/settings/smtp', [SettingsController::class, 'smtp'])->name('settings.smtp');
    Route::put('/settings/smtp', [SettingsController::class, 'updateSmtp'])->name('settings.smtp.update');
    Route::get('/settings/account-deletion', [SettingsController::class, 'accountDeletion'])->name('settings.account_deletion');
    Route::put('/settings/account-deletion', [SettingsController::class, 'updateAccountDeletion'])->name('settings.account_deletion.update');
    Route::get('/settings/course', [SettingsController::class, 'coursePolicies'])->name('settings.course');
    Route::put('/settings/course', [SettingsController::class, 'updateCoursePolicies'])->name('settings.course.update');
    Route::get('/settings/branding', [SettingsController::class, 'branding'])->name('settings.branding');
    Route::put('/settings/branding', [SettingsController::class, 'updateBranding'])->name('settings.branding.update');
    Route::get('/settings/chat', [SettingsController::class, 'chat'])->name('settings.chat');
    Route::put('/settings/chat', [SettingsController::class, 'updateChat'])->name('settings.chat.update');
    Route::get('/settings/contact', [SettingsController::class, 'contact'])->name('settings.contact');
    Route::put('/settings/contact', [SettingsController::class, 'updateContact'])->name('settings.contact.update');
    Route::get('/settings/authentication', [SettingsController::class, 'authentication'])->name('settings.authentication');
    Route::put('/settings/authentication', [SettingsController::class, 'updateAuthentication'])->name('settings.authentication.update');
    Route::get('/settings/recaptcha', [SettingsController::class, 'recaptcha'])->name('settings.recaptcha');
    Route::put('/settings/recaptcha', [SettingsController::class, 'updateRecaptcha'])->name('settings.recaptcha.update');
    Route::get('/settings/revenue', [SettingsController::class, 'revenue'])->name('settings.revenue');
    Route::put('/settings/revenue', [SettingsController::class, 'updateRevenue'])->name('settings.revenue.update');
    Route::get('/settings/tax', [SettingsController::class, 'tax'])->name('settings.tax');
    Route::put('/settings/tax', [SettingsController::class, 'updateTax'])->name('settings.tax.update');
    Route::get('/settings/withdrawals', [SettingsController::class, 'withdrawals'])->name('settings.withdrawals');
    Route::put('/settings/withdrawals', [SettingsController::class, 'updateWithdrawals'])->name('settings.withdrawals.update');
    Route::get('/settings/support', [SettingsController::class, 'support'])->name('settings.support');
    Route::put('/settings/support', [SettingsController::class, 'updateSupport'])->name('settings.support.update');
    Route::get('/settings/custom-scripts', [SettingsController::class, 'customScripts'])->name('settings.custom_scripts');
    Route::put('/settings/custom-scripts', [SettingsController::class, 'updateCustomScripts'])->name('settings.custom_scripts.update');
    Route::get('/settings/cron-setup', [SettingsController::class, 'cronSetup'])->name('settings.cron_setup');
    Route::put('/settings/cron-setup', [SettingsController::class, 'updateCronSetup'])->name('settings.cron_setup.update');
    Route::get('/settings/live-class', [SettingsController::class, 'liveClassSettings'])->name('settings.live_class');
    Route::put('/settings/live-class', [SettingsController::class, 'updateLiveClassSettings'])->name('settings.live_class.update');
    Route::get('/settings/gateways', [PaymentGatewayController::class, 'index'])->name('settings.gateways.index');
    Route::put('/settings/gateways/{identifier}', [PaymentGatewayController::class, 'update'])->name('settings.gateways.update');

    // Language Manager
    Route::get('/settings/languages', [LanguageController::class, 'index'])->name('languages.index');
    Route::get('/settings/languages/create', [LanguageController::class, 'create'])->name('languages.create');
    Route::post('/settings/languages', [LanguageController::class, 'store'])->name('languages.store');
    Route::delete('/settings/languages/{locale}', [LanguageController::class, 'destroy'])->name('languages.destroy');
    Route::get('/settings/languages/{locale}/translations', [LanguageController::class, 'editTranslations'])->name('languages.translations.edit');
    Route::post('/settings/languages/{locale}/translations', [LanguageController::class, 'updateTranslations'])->name('languages.translations.update');
    Route::post('/settings/languages/{locale}/translations/add-key', [LanguageController::class, 'addTranslationKey'])->name('languages.translations.add-key');

    Route::resource('custom-pages', CustomPageController::class)->only(['index', 'edit', 'update']);
    Route::prefix('settings/system')->name('settings.system.')->group(function () {
        Route::get('/status', [SystemMaintenanceController::class, 'status'])->name('status');
        Route::get('/cache', [SystemMaintenanceController::class, 'cache'])->name('cache');
        Route::post('/cache/clear', [SystemMaintenanceController::class, 'clearCache'])->name('cache.clear');
        Route::post('/storage-link', [SystemMaintenanceController::class, 'linkStorage'])->name('storage.link');
        Route::get('/logs', [SystemMaintenanceController::class, 'logs'])->name('logs');
    });

    // Complete Admin Backup Management System (v2.2.0)
    Route::prefix('backups')->name('backups.')->group(function () {
        Route::get('/', [BackupController::class, 'index'])->name('index');
        Route::post('/create', [BackupController::class, 'create'])->name('create');
        Route::get('/{fileName}/download', [BackupController::class, 'download'])->name('download')->where('fileName', '[A-Za-z0-9._-]+');
        Route::delete('/{fileName}', [BackupController::class, 'destroy'])->name('destroy')->where('fileName', '[A-Za-z0-9._-]+');
    });

    // Staff, Super Admin & Role Management with Granular Permissions (v2.2.0)
    Route::prefix('staff')->name('staff.')->group(function () {
        Route::get('/', [StaffController::class, 'index'])->name('index');
        Route::get('/create', [StaffController::class, 'create'])->name('create');
        Route::post('/', [StaffController::class, 'store'])->name('store');
        Route::get('/{staff}/edit', [StaffController::class, 'edit'])->name('edit');
        Route::put('/{staff}', [StaffController::class, 'update'])->name('update');
        Route::delete('/{staff}', [StaffController::class, 'destroy'])->name('destroy');
        Route::post('/roles', [StaffController::class, 'storeRole'])->name('roles.store');
        Route::put('/roles/{role}', [StaffController::class, 'updateRole'])->name('roles.update');
        Route::delete('/roles/{role}', [StaffController::class, 'destroyRole'])->name('roles.destroy');
    });

    // Support Ticket System (v3.0.0)
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', [AdminSupportTicketController::class, 'index'])->name('index');
        Route::get('/{ticket}', [AdminSupportTicketController::class, 'show'])->name('show');
        Route::post('/{ticket}/reply', [AdminSupportTicketController::class, 'reply'])->name('reply');
        Route::put('/{ticket}/status', [AdminSupportTicketController::class, 'updateStatus'])->name('status');
        Route::delete('/{ticket}', [AdminSupportTicketController::class, 'destroy'])->name('destroy');
    });
    Route::get('/settings/newsletter', [AdminNewsletterController::class, 'index'])->name('settings.newsletter.index');
    Route::post('/settings/newsletter/send', [AdminNewsletterController::class, 'send'])->name('settings.newsletter.send');

    // Instructor management
    Route::get('/instructors', [InstructorController::class, 'index'])->name('instructors.index');
    Route::get('/instructors/create', [InstructorController::class, 'create'])->name('instructors.create');
    Route::post('/instructors', [InstructorController::class, 'store'])->name('instructors.store');
    Route::get('/instructors/pending', [InstructorController::class, 'pending'])->name('instructors.pending');
    Route::get('/instructors/{id}/edit', [InstructorController::class, 'edit'])->name('instructors.edit');
    Route::put('/instructors/{id}', [InstructorController::class, 'update'])->name('instructors.update');
    Route::delete('/instructors/{id}', [InstructorController::class, 'destroy'])->name('instructors.destroy');
    Route::get('/instructors/{id}', [InstructorController::class, 'show'])->name('instructors.show');
    Route::post('/instructors/{id}/approve', [InstructorController::class, 'approve'])->name('instructors.approve');
    Route::post('/instructors/{id}/reject', [InstructorController::class, 'reject'])->name('instructors.reject');

    // Student management
    Route::get('/students', [StudentController::class, 'index'])->name('students.index');
    Route::get('/students/export', [StudentController::class, 'export'])->name('students.export');
    Route::get('/students/create', [StudentController::class, 'create'])->name('students.create');
    Route::post('/students', [StudentController::class, 'store'])->name('students.store');
    Route::get('/students/{id}/edit', [StudentController::class, 'edit'])->name('students.edit');
    Route::put('/students/{id}', [StudentController::class, 'update'])->name('students.update');
    Route::delete('/students/{id}', [StudentController::class, 'destroy'])->name('students.destroy');
    Route::get('/students/{id}', [StudentController::class, 'show'])->name('students.show');

    // Enrollment management
    Route::get('/enrollments', [EnrollmentController::class, 'index'])->name('enrollments.index');
    Route::get('/enrollments/pending', [EnrollmentController::class, 'pending'])->name('enrollments.pending');
    Route::get('/enrollments/completed', [EnrollmentController::class, 'completed'])->name('enrollments.completed');
    Route::get('/enrollments/{id}', [EnrollmentController::class, 'show'])->name('enrollments.show');
    Route::put('/enrollments/{id}/update-status', [EnrollmentController::class, 'updateStatus'])->name('enrollments.update-status');
    Route::post('/enrollments/{id}/approve', [EnrollmentController::class, 'approve'])->name('enrollments.approve');
    Route::post('/enrollments/{id}/reject', [EnrollmentController::class, 'reject'])->name('enrollments.reject');
    Route::post('/enrollments/{id}/cancel', [EnrollmentController::class, 'cancel'])->name('enrollments.cancel');
    Route::post('/enrollments/{id}/complete', [EnrollmentController::class, 'complete'])->name('enrollments.complete');

    // Payment management
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/pending', [PaymentController::class, 'pending'])->name('payments.pending');
    Route::get('/payments/offline-pending', [PaymentController::class, 'offlinePending'])->name('payments.offline-pending');
    Route::get('/payments/{id}', [PaymentController::class, 'show'])->name('payments.show');
    Route::put('/payments/{id}/update-status', [PaymentController::class, 'updateStatus'])->name('payments.update-status');
    Route::post('/payments/{id}/approve', [PaymentController::class, 'approve'])->name('payments.approve');
    Route::post('/payments/{id}/reject', [PaymentController::class, 'reject'])->name('payments.reject');

    // Reports & Analytics (Online + Merged Regional SCMS Reports)
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/center-courses', [ReportsController::class, 'centerCourseReport'])->name('reports.center-courses');
    Route::get('/reports/applicants', [ReportsController::class, 'applicantReport'])->name('reports.applicants');

    // Audit Logs & Online Help
    Route::get('/audit-logs', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('/help', [\App\Http\Controllers\HelpController::class, 'index'])->name('help.index');

    // Certificates
    Route::get('/certificates', [CertificateController::class, 'index'])->name('certificates.index');
    Route::get('/certificates/{enrollment}/download', [CertificateController::class, 'download'])->name('certificates.download');

    // Certificate Templates (Drag & Drop Builder)
    Route::prefix('certificates/templates')->name('certificates.templates.')->group(function () {
        Route::get('/', [CertificateTemplateController::class, 'index'])->name('index');
        Route::get('/create', [CertificateTemplateController::class, 'create'])->name('create');
        Route::post('/', [CertificateTemplateController::class, 'store'])->name('store');
        Route::post('/install-prebuilt', [CertificateTemplateController::class, 'installPrebuilt'])->name('install-prebuilt');
        Route::get('/{template}/edit', [CertificateTemplateController::class, 'edit'])->name('edit');
        Route::put('/{template}', [CertificateTemplateController::class, 'update'])->name('update');
        Route::delete('/{template}', [CertificateTemplateController::class, 'destroy'])->name('destroy');
        Route::post('/{template}/default', [CertificateTemplateController::class, 'setDefault'])->name('default');
        Route::post('/{template}/duplicate', [CertificateTemplateController::class, 'duplicate'])->name('duplicate');
        Route::get('/{template}/preview', [CertificateTemplateController::class, 'preview'])->name('preview');
        Route::put('/courses/{course}/assign', [CertificateTemplateController::class, 'assignToCourse'])->name('assign');
    });

    // Payout management
    Route::get('/withdrawals', [WithdrawalController::class, 'index'])->name('withdrawals.index');
    Route::get('/withdrawals/{withdrawal}', [WithdrawalController::class, 'show'])->name('withdrawals.show');
    Route::put('/withdrawals/{withdrawal}/status', [WithdrawalController::class, 'updateStatus'])->name('withdrawals.status');

    // Account deletion requests
    Route::get('/account-deletions', [AccountDeletionController::class, 'index'])->name('account-deletions.index');
    Route::put('/account-deletions/{accountDeletionRequest}/approve', [AccountDeletionController::class, 'approve'])->name('account-deletions.approve');
    Route::put('/account-deletions/{accountDeletionRequest}/reject', [AccountDeletionController::class, 'reject'])->name('account-deletions.reject');

    // Review management
    Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::get('/reviews/pending', [ReviewController::class, 'pending'])->name('reviews.pending');
    Route::get('/reviews/{id}', [ReviewController::class, 'show'])->name('reviews.show');
    Route::post('/reviews/{id}/approve', [ReviewController::class, 'approve'])->name('reviews.approve');
    Route::post('/reviews/{id}/reject', [ReviewController::class, 'reject'])->name('reviews.reject');
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::delete('/reviews/{reviewId}/replies/{replyId}', [ReviewController::class, 'deleteReply'])->name('reviews.delete-reply');

    // Impersonation
    Route::post('/impersonate/instructor/{id}', [ImpersonationController::class, 'impersonate'])->name('impersonate.instructor');
    Route::post('/impersonate/student/{id}', [ImpersonationController::class, 'impersonateStudent'])->name('impersonate.student');

    // User management
    Route::resource('users', UserController::class)->only(['index', 'show', 'destroy']);

    // Course management
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/create', [SharedCourseBuilderController::class, 'create'])->name('courses.create');
    Route::post('/courses', [SharedCourseBuilderController::class, 'store'])->name('courses.store');
    Route::post('/courses/ai-builder', [SharedCourseBuilderController::class, 'aiCourseBuilder'])->name('courses.ai-builder');
    Route::post('/courses/ai-studio', [SharedCourseBuilderController::class, 'aiStudio'])->name('courses.ai-studio');
    Route::post('/courses/import-youtube-playlist', [SharedCourseBuilderController::class, 'importYoutubePlaylist'])->name('courses.import-youtube-playlist');
    Route::get('/courses/pending', [CourseController::class, 'pending'])->name('courses.pending');
    Route::get('/courses/{id}/edit', [SharedCourseBuilderController::class, 'edit'])->name('courses.edit');
    Route::put('/courses/{id}', [SharedCourseBuilderController::class, 'update'])->name('courses.update');
    Route::get('/courses/{id}', [CourseController::class, 'show'])->name('courses.show');
    Route::post('/courses/{id}/approve', [CourseController::class, 'approve'])->name('courses.approve');
    Route::post('/courses/{id}/reject', [CourseController::class, 'reject'])->name('courses.reject');
    Route::post('/courses/{id}/update-status', [CourseController::class, 'updateStatus'])->name('courses.update-status');
    Route::delete('/courses/{id}', [CourseController::class, 'destroy'])->name('courses.destroy');
    Route::delete('/courses/{id}/full-delete', [CourseController::class, 'fullDelete'])->name('courses.full-delete');

    // Bundle management
    Route::resource('bundles', BundleController::class);
    Route::post('/bundles/{bundle}/approve', [BundleController::class, 'approve'])->name('bundles.approve');
    Route::post('/bundles/{bundle}/reject', [BundleController::class, 'reject'])->name('bundles.reject');

    Route::resource('course-categories', CourseCategoryController::class);
    Route::resource('course-languages', CourseLanguageController::class);

    // Frontend Manager
    Route::resource('banners', BannerController::class);
    Route::prefix('frontend')->name('frontend.')->group(function () {
        Route::resource('menus', MenuController::class);
        Route::post('menus/{menu}/items/order', [MenuItemController::class, 'reorder'])->name('menus.items.reorder');
        Route::post('menus/{menu}/items', [MenuItemController::class, 'store'])->name('menus.items.store');
        Route::put('menus/{menu}/items/{menuItem}', [MenuItemController::class, 'update'])->name('menus.items.update');
        Route::delete('menus/{menu}/items/{menuItem}', [MenuItemController::class, 'destroy'])->name('menus.items.destroy');

        Route::get('settings', [FrontendSettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [FrontendSettingController::class, 'update'])->name('settings.update');
        Route::get('faq', [FrontendSettingController::class, 'faq'])->name('faq.edit');
        Route::put('faq', [FrontendSettingController::class, 'updateFaq'])->name('faq.update');
        Route::get('seo', [FrontendSettingController::class, 'seo'])->name('seo.edit');
        Route::put('seo', [FrontendSettingController::class, 'updateSeo'])->name('seo.update');
        Route::get('marquee', [FrontendSettingController::class, 'marquee'])->name('marquee.edit');
        Route::put('marquee', [FrontendSettingController::class, 'updateMarquee'])->name('marquee.update');
        Route::get('home', [FrontendSettingController::class, 'homepage'])->name('home.edit');
        Route::put('home', [FrontendSettingController::class, 'updateHomepage'])->name('home.update');
        Route::get('about', [FrontendSettingController::class, 'about'])->name('about.edit');
        Route::put('about', [FrontendSettingController::class, 'updateAbout'])->name('about.update');
        Route::get('contact', [FrontendSettingController::class, 'contact'])->name('contact.edit');
        Route::put('contact', [FrontendSettingController::class, 'updateContact'])->name('contact.update');

        // Theme management routes
        Route::get('theme', [FrontendSettingController::class, 'theme'])->name('theme.edit');
        Route::put('theme', [FrontendSettingController::class, 'updateTheme'])->name('theme.update');
        Route::post('theme/upload', [FrontendSettingController::class, 'uploadTheme'])->name('theme.upload');
        Route::delete('theme', [FrontendSettingController::class, 'deleteTheme'])->name('theme.delete');
    });

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Learning activity
    Route::get('/learning/quiz-attempts', [QuizAttemptController::class, 'index'])->name('quiz-attempts.index');
    Route::get('/learning/quiz-attempts/{quizAttempt}', [QuizAttemptController::class, 'show'])->name('quiz-attempts.show');
    Route::get('/learning/assignment-submissions', [AssignmentSubmissionController::class, 'index'])->name('assignment-submissions.index');
    Route::get('/learning/assignment-submissions/{assignmentSubmission}', [AssignmentSubmissionController::class, 'show'])->name('assignment-submissions.show');

    // Certificate management
    Route::resource('certificates', CertificateController::class);

    // Notifications
    Route::resource('notifications', NotificationController::class);
    Route::post('notifications/{id}/resend', [NotificationController::class, 'resend'])->name('notifications.resend');

    // Coupons
    Route::resource('coupons', CouponController::class);

    // ===== Campus & Short Courses (Face-to-Face / Hybrid) =====
    Route::prefix('campus')->name('campus.')->group(function () {
        Route::get('search', [\App\Http\Controllers\Admin\Campus\GlobalSearchController::class, 'search'])->name('search');

        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('active-classes', [\App\Http\Controllers\Admin\Campus\AnalyticsController::class, 'activeClasses'])->name('active-classes');
            Route::get('branches', [\App\Http\Controllers\Admin\Campus\AnalyticsController::class, 'branches'])->name('branches');
            Route::get('discounts', [\App\Http\Controllers\Admin\Campus\AnalyticsController::class, 'discounts'])->name('discounts');
            Route::get('tutor-workload', [\App\Http\Controllers\Admin\Campus\AnalyticsController::class, 'tutorWorkload'])->name('tutor-workload');
        });

        Route::resource('branches', \App\Http\Controllers\Admin\Campus\BranchController::class)->except(['create', 'show']);
        Route::resource('venues', \App\Http\Controllers\Admin\Campus\VenueController::class)->except(['create', 'show']);
        Route::get('branches/{branch}/venues', [\App\Http\Controllers\Admin\Campus\VenueController::class, 'byBranch'])->name('venues.by-branch');
        Route::resource('countries', \App\Http\Controllers\Admin\Campus\CountryController::class)->except(['create', 'show', 'edit']);

        Route::resource('timetable', \App\Http\Controllers\Admin\Campus\CourseAssignmentController::class)->except(['show']);
        Route::post('timetable/{timetable}/toggle-status', [\App\Http\Controllers\Admin\Campus\CourseAssignmentController::class, 'toggleStatus'])->name('timetable.toggle-status');

        Route::get('applicants', [\App\Http\Controllers\Admin\Campus\ApplicantController::class, 'index'])->name('applicants.index');
        Route::get('applicants/create', [\App\Http\Controllers\Admin\Campus\ApplicantController::class, 'create'])->name('applicants.create');
        Route::post('applicants', [\App\Http\Controllers\Admin\Campus\ApplicantController::class, 'store'])->name('applicants.store');
        Route::get('applicants/{applicant}', [\App\Http\Controllers\Admin\Campus\ApplicantController::class, 'show'])->name('applicants.show');
        Route::get('applicants/course-fee/{course}', [\App\Http\Controllers\Admin\Campus\ApplicantController::class, 'courseFee'])->name('applicants.course-fee');

        Route::get('invoices', [\App\Http\Controllers\Admin\Campus\InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('invoices/{invoice}', [\App\Http\Controllers\Admin\Campus\InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('invoices/{invoice}/print', [\App\Http\Controllers\Admin\Campus\InvoiceController::class, 'print'])->name('invoices.print');
        Route::get('invoices/payments/{payment}/receipt', [\App\Http\Controllers\Admin\Campus\InvoiceController::class, 'receipt'])->name('invoices.receipt');
        Route::post('invoices/{invoice}/payments', [\App\Http\Controllers\Admin\Campus\InvoiceController::class, 'recordPayment'])->name('invoices.payments.store');
        Route::post('invoices/{invoice}/control-number', [\App\Http\Controllers\Admin\Campus\InvoiceController::class, 'regenerateControlNumber'])->name('invoices.control-number');
        Route::post('invoices/payments/{payment}/verify', [\App\Http\Controllers\Admin\Campus\InvoiceController::class, 'verifyPayment'])->name('invoices.payments.verify');

        Route::get('installments', [\App\Http\Controllers\Admin\Campus\PaymentScheduleController::class, 'index'])->name('installments.index');
        Route::get('installments/create', [\App\Http\Controllers\Admin\Campus\PaymentScheduleController::class, 'create'])->name('installments.create');
        Route::post('installments/generate', [\App\Http\Controllers\Admin\Campus\PaymentScheduleController::class, 'generate'])->name('installments.generate');
        Route::get('installments/reminders', [\App\Http\Controllers\Admin\Campus\PaymentScheduleController::class, 'reminders'])->name('installments.reminders-list');
        Route::post('installments/reminders', [\App\Http\Controllers\Admin\Campus\PaymentScheduleController::class, 'sendReminders'])->name('installments.reminders');
        Route::get('installments/invoice/{invoice}', [\App\Http\Controllers\Admin\Campus\PaymentScheduleController::class, 'show'])->name('installments.show');
        Route::put('installments/{schedule}', [\App\Http\Controllers\Admin\Campus\PaymentScheduleController::class, 'update'])->name('installments.update');
        Route::post('installments/{schedule}/mark-sent', [\App\Http\Controllers\Admin\Campus\PaymentScheduleController::class, 'markAsSent'])->name('installments.mark-sent');
        Route::post('installments/{schedule}/cancel', [\App\Http\Controllers\Admin\Campus\PaymentScheduleController::class, 'cancel'])->name('installments.cancel');
        Route::delete('installments/{schedule}', [\App\Http\Controllers\Admin\Campus\PaymentScheduleController::class, 'destroy'])->name('installments.destroy');

        Route::get('attendance', [\App\Http\Controllers\Admin\Campus\AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('attendance/{assignment}', [\App\Http\Controllers\Admin\Campus\AttendanceController::class, 'show'])->name('attendance.show');
        Route::post('attendance/{assignment}', [\App\Http\Controllers\Admin\Campus\AttendanceController::class, 'store'])->name('attendance.store');
        Route::post('attendance/{assignment}/qr', [\App\Http\Controllers\Admin\Campus\AttendanceController::class, 'openQr'])->name('attendance.qr');
        Route::get('attendance/{assignment}/export', [\App\Http\Controllers\Admin\Campus\AttendanceController::class, 'export'])->name('attendance.export');

        Route::get('certificates', [\App\Http\Controllers\Admin\Campus\CertificateClearanceController::class, 'index'])->name('certificates.index');
        Route::post('certificates/{enrollment}', [\App\Http\Controllers\Admin\Campus\CertificateClearanceController::class, 'update'])->name('certificates.update');
        Route::get('certificates/{enrollment}/pdf', [\App\Http\Controllers\Admin\Campus\CertificateClearanceController::class, 'downloadPdf'])->name('certificates.pdf');

        Route::get('templates', [\App\Http\Controllers\Admin\Campus\NotificationTemplateController::class, 'index'])->name('templates.index');
        Route::put('templates/{template}', [\App\Http\Controllers\Admin\Campus\NotificationTemplateController::class, 'update'])->name('templates.update');
    });

    // SRS account security actions (merged into existing user management)
    Route::post('users/{user}/block', [\App\Http\Controllers\Admin\UserSecurityController::class, 'block'])->name('user-security.block');
    Route::post('users/{user}/unblock', [\App\Http\Controllers\Admin\UserSecurityController::class, 'unblock'])->name('user-security.unblock');
    Route::post('users/{user}/force-reset', [\App\Http\Controllers\Admin\UserSecurityController::class, 'forceReset'])->name('user-security.force-reset');


    // System Updater
    Route::get('/updater', [\App\Http\Controllers\Admin\UpdaterController::class, 'index'])->name('updater.index');
    Route::post('/updater/run', [\App\Http\Controllers\Admin\UpdaterController::class, 'update'])->name('updater.update');
});

