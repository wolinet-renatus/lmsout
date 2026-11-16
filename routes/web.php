<?php

/**
 * Public Web & Storefront Routes
 *
 * Defines public catalog, live search, F2F/Hybrid application intake,
 * certificate verification, custom CMS pages, and payment gateway callbacks.
 *
 * @author Renatus Bernardo
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Front\CoursesController;
use App\Http\Controllers\Front\CourseEnrollmentController;
use App\Http\Controllers\Front\ReviewController;
use App\Http\Controllers\Front\AboutController;
use App\Http\Controllers\Front\ContactController;
use App\Http\Controllers\Front\InstructorController;
use App\Http\Controllers\Front\TestimonialController;
use App\Http\Controllers\Front\FaqController;
use App\Http\Controllers\Front\NewsletterController;
use App\Http\Controllers\Front\PageController;
use App\Http\Controllers\Front\HomeController;
use App\Http\Controllers\Front\CurrencyController;
use App\Http\Controllers\Front\BundleController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Front\CertificateVerificationController;
use App\Models\CustomPage;

Route::get('/', HomeController::class)->name('home');
Route::get('/currency/{code}', CurrencyController::class)->name('currency.switch');
Route::match(['get', 'post'], '/verify-certificate', [CertificateVerificationController::class, 'verify'])->name('verify-certificate');

// Terms and conditions routes
Route::redirect('/terms-conditions', '/pages/terms-and-conditions', 301);

// About and contact routes
Route::get('/about', AboutController::class)->name('about');
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'submit'])->name('contact.submit');

// Instructors routes
Route::get('/instructors', [InstructorController::class, 'index'])->name('instructors.index');
Route::get('/instructors/{instructor}', [InstructorController::class, 'show'])
    ->whereNumber('instructor')
    ->name('instructors.show');

// Testimonials and newsletter routes
Route::get('/testimonials', [TestimonialController::class, 'index'])->name('testimonials.index');
Route::get('/pages/{customPage:slug}', PageController::class)->name('pages.show');
Route::post('/newsletter/subscribe', [NewsletterController::class, 'store'])->name('newsletter.subscribe');

// Custom pages routes
try {
    $customPageSlugs = CustomPage::query()->pluck('slug')->filter()->all();

    if (!empty($customPageSlugs)) {
        Route::get('/{customPage:slug}', PageController::class)
            ->whereIn('customPage', $customPageSlugs)
            ->name('pages.legacy');
    }
} catch (\Throwable $exception) {
        
}

// FAQ routes
Route::get('/faq', FaqController::class)->name('faq');

// Courses & Live Search routes
Route::get('/live-search', [CoursesController::class, 'liveSearch'])->name('live-search');
Route::get('/courses', [CoursesController::class, 'index'])->name('courses');
Route::get('/courses/apply/fee/{course}', [\App\Http\Controllers\Front\CourseApplyController::class, 'courseFee'])->name('courses.apply.fee');
Route::get('/courses/{course}/apply', [\App\Http\Controllers\Front\CourseApplyController::class, 'show'])->name('courses.apply');
Route::post('/courses/{course}/apply', [\App\Http\Controllers\Front\CourseApplyController::class, 'store'])->name('courses.apply.store');
Route::get('/courses/apply/{invoice}/confirmation', [\App\Http\Controllers\Front\CourseApplyController::class, 'confirmation'])->name('courses.apply.confirmation');
Route::get('/courses/apply/{invoice}/print', [\App\Http\Controllers\Front\CourseApplyController::class, 'print'])->name('courses.apply.print');
Route::get('/campus/invoice-verify/{number}', [\App\Http\Controllers\Front\CourseApplyController::class, 'verifyInvoice'])->name('campus.invoice.verify');
Route::get('/courses/{course}', [CoursesController::class, 'show'])->name('courses.show');

// Bundle public routes
Route::get('/bundles', [BundleController::class, 'index'])->name('bundles.index');
Route::get('/bundles/{id}', [BundleController::class, 'show'])->name('bundles.show');

// Payment callbacks
Route::get('/payment/stripe/callback', [CourseEnrollmentController::class, 'stripeCallback'])->name('payment.stripe.callback');
Route::get('/payment/flutterwave/callback', [CourseEnrollmentController::class, 'flutterwaveCallback'])->name('payment.flutterwave.callback');
Route::get('/payment/paypal/callback', [CourseEnrollmentController::class, 'paypalCallback'])->name('payment.paypal.callback');
Route::match(['get', 'post'], '/payment/pesapal/callback', [CourseEnrollmentController::class, 'pesapalCallback'])->name('payment.pesapal.callback');
Route::match(['get', 'post'], '/payment/pesapal/ipn', [CourseEnrollmentController::class, 'pesapalIpn'])->name('payment.pesapal.ipn');
Route::get('/payment/pesapal/status', [CourseEnrollmentController::class, 'pesapalStatus'])->name('payment.pesapal.status');

// Course checkout routes
Route::middleware(['auth'])->group(function () {

    Route::get('/courses/{id}/checkout', [CourseEnrollmentController::class, 'checkout'])->name('courses.checkout');
    Route::post('/courses/{id}/checkout', [CourseEnrollmentController::class, 'processCheckout'])->name('courses.checkout.process');
    Route::get('/bundles/{id}/checkout', [CourseEnrollmentController::class, 'checkoutBundle'])->name('bundles.checkout');
    Route::post('/bundles/{id}/checkout', [CourseEnrollmentController::class, 'processCheckoutBundle'])->name('bundles.checkout.process');
    Route::get('/enrollment/success/{id}', [CourseEnrollmentController::class, 'success'])->name('enrollment.success');
    Route::post('/coupons/validate', [CourseEnrollmentController::class, 'validateCoupon'])->name('coupons.validate');

    // Courses reviews routes
    Route::post('/courses/{courseId}/reviews', [ReviewController::class, 'store'])->name('courses.reviews.store');
    Route::put('/courses/{courseId}/reviews/{reviewId}', [ReviewController::class, 'update'])->name('courses.reviews.update');

    // Admin impersonation routes
    Route::post('/admin/stop-impersonating', [ImpersonationController::class, 'stopImpersonating'])->name('admin.stop-impersonating');
});

// Cron runner route
Route::get('/cron/run', [App\Http\Controllers\CronController::class, 'run'])->name('cron.run');

// Include all route files
require __DIR__ . '/auth.php';
require __DIR__ . '/student.php';
require __DIR__ . '/instructor.php';
require __DIR__ . '/admin.php';
