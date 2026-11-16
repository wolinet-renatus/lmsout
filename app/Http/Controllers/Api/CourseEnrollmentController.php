<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Services\SettingsRepository;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use App\Services\FileUploadService;
use App\Services\PaymentGatewayService;
use App\Services\NotificationPreferenceService;
use App\Services\RevenueShareService;
use App\Services\CouponService;
use App\Models\CouponUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use App\Mail\StudentCourseEnrollmentMail;
use App\Mail\InstructorCourseEnrollmentMail;
use Illuminate\Support\Facades\Mail;

/**
 * Course enrollment controller API Controller — handles public and authenticated Sanctum REST endpoints.
 *
 * @author Renatus Bernardo
 */
class CourseEnrollmentController extends Controller
{
    protected $fileUploadService;
    protected PaymentGatewayService $paymentGatewayService;

    /**
     * Initialize dependencies for this class.
     */
    public function __construct(
        FileUploadService $fileUploadService,
        PaymentGatewayService $paymentGatewayService,
        protected SettingsRepository $settingsRepository,
        private NotificationPreferenceService $notificationPreferenceService,
        private RevenueShareService $revenueShareService,
        private CouponService $couponService
    ) {
        $this->fileUploadService = $fileUploadService;
        $this->paymentGatewayService = $paymentGatewayService;
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * Process checkout
     */
    public function enrollBundle(CheckoutRequest $request, $id)
    {
        $request->merge(['type' => 'bundle']);
        return $this->enroll($request, $id);
    }

    /**
     * Enroll.
     */
    public function enroll(CheckoutRequest $request, $id)
    {
        $user = Auth::user();
        $isBundle = $request->input('type') === 'bundle';
        
        if ($isBundle) {
            $course = \App\Models\Bundle::with('courses')->findOrFail($id);
            if ($course->status !== 'active' || $course->approval_status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'This bundle is not available.'
                ], 400);
            }

            $ownedCourseIds = Enrollment::where('user_id', $user->id)
                ->whereIn('status', ['approved', 'completed'])
                ->pluck('course_id')
                ->toArray();

            if (!$request->input('confirm_duplicate')) {
                $bundleCourseIds = $course->courses->pluck('id')->toArray();
                if (count(array_intersect($ownedCourseIds, $bundleCourseIds)) > 0) {
                    return response()->json([
                        'success' => false,
                        'is_duplicate_warning' => true,
                        'message' => 'You already own some courses in this bundle. Proceeding will enroll you in the remaining courses and charge the full bundle price.'
                    ], 400);
                }
            }

            $unownedCourses = $course->courses->whereNotIn('id', $ownedCourseIds);
            if ($unownedCourses->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'You already own all courses in this bundle.'], 400);
            }

            $price = (float) $course->price;
            $courseIdToUse = $unownedCourses->first()->id;
        } else {
            $course = Course::findOrFail($id);
            if ($course->status !== 'published' || $course->visibility !== 'public') {
                return response()->json([
                    'success' => false,
                    'message' => 'This course is not available for enrollment.'
                ], 400);
            }

            if ($request->filled('invoice_id')) {
                $campusInvoice = \App\Models\Invoice::withoutGlobalScope('branch')
                    ->with('paymentSchedules')
                    ->where('id', $request->integer('invoice_id'))
                    ->where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->firstOrFail();

                if ($campusInvoice->balance <= 0) {
                    return response()->json(['success' => false, 'message' => 'This invoice is already fully paid.'], 422);
                }

                if (strtolower((string) $request->payment_method) !== Payment::PAYMENT_METHOD_OFFLINE && ! $course->allow_online_f2f_payment) {
                    return response()->json(['success' => false, 'message' => 'Online installment payment is disabled for this course.'], 422);
                }

                $nextSched = $campusInvoice->paymentSchedules->where('status', '!=', 'paid')->sortBy('installment_number')->first();
                $price = ($request->input('pay') === 'balance' || ! $nextSched)
                    ? (float) $campusInvoice->balance
                    : round((float) $nextSched->amount_due - (float) $nextSched->amount_paid, 2);

                Payment::creating(function (Payment $payment) use ($campusInvoice): void {
                    if (! $payment->invoice_id && (int) $payment->user_id === (int) $campusInvoice->user_id) {
                        $payment->invoice_id = $campusInvoice->id;
                        $payment->applicant_id = $campusInvoice->applicant_id;
                        $payment->installment_type = $campusInvoice->installment_plan;
                        if ($campusInvoice->enrollment_id) {
                            $payment->enrollment_id = $campusInvoice->enrollment_id;
                        }
                    }
                });
            } elseif ($course->requiresApplicantForm()) {
                return response()->json([
                    'success' => false,
                    'requires_applicant_form' => true,
                    'apply_endpoint' => url('/api/campus/courses/' . $course->id . '/apply'),
                    'message' => 'Face-to-Face and Hybrid courses require submitting the campus application form before checkout.',
                ], 422);
            } else {
                if (strtolower((string) $request->payment_method) === Payment::PAYMENT_METHOD_OFFLINE && $course->isOnline()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Online courses require direct online payment.',
                    ], 422);
                }

                $existingEnrollment = Enrollment::where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->first();

                if ($existingEnrollment) {
                    if (in_array($existingEnrollment->status, [Enrollment::STATUS_APPROVED, Enrollment::STATUS_COMPLETED])) {
                        return response()->json(['success' => false, 'message' => 'You are already enrolled.'], 400);
                    }
                    $payment = $existingEnrollment->payment;
                    if ($payment && $payment->payment_method === Payment::PAYMENT_METHOD_OFFLINE) {
                        return response()->json(['success' => false, 'message' => 'Pending offline payment request exists.'], 400);
                    }
                    $existingEnrollment->delete();
                }

                $price = $this->calculatePrice($course);
            }
            $courseIdToUse = $course->id;
        }
        $paymentMethod = strtolower($request->payment_method);

        // Apply coupon if provided (only for single-course enrollment, not bundles)
        $appliedCoupon = null;
        $discountAmount = 0.0;
        if (!$isBundle && $request->filled('coupon_code') && $price > 0) {
            try {
                $appliedCoupon = $this->couponService->validate($request->input('coupon_code'), $course, $user);
                $discountAmount = $this->couponService->calculateDiscount($appliedCoupon, $price);
                $price = max(0, $price - $discountAmount);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
        }

        if ($price > 0 && !$this->paymentGatewayService->isEnabled($paymentMethod)) {
            return response()->json([
                'success' => false,
                'message' => 'Selected payment method is not available.'
            ], 400);
        }

        DB::beginTransaction();

        try {
            if ($isBundle) {
                // Ensure BundleEnrollment exists
                \App\Models\BundleEnrollment::updateOrCreate(
                    ['user_id' => $user->id, 'bundle_id' => $course->id],
                    ['status' => 'pending']
                );
                // Pre-generate pending Course Enrollments for ALL UNOWNED courses
                // Already approved/completed enrollments are skipped entirely.
                foreach ($course->courses as $bundleCourseItem) {
                    if ($bundleCourseItem->id !== $courseIdToUse && !in_array($bundleCourseItem->id, $ownedCourseIds)) {
                        Enrollment::firstOrCreate(
                            ['user_id' => $user->id, 'course_id' => $bundleCourseItem->id],
                            ['status' => Enrollment::STATUS_PENDING]
                        );
                    }
                }
            }
            if ($price > 0 && $paymentMethod === Payment::PAYMENT_METHOD_STRIPE) {
                $credentials = $this->credentials('stripe');
                $stripeSecret = $credentials['secret'] ?? '';

                if (empty($stripeSecret)) {
                    throw new \Exception('Stripe credentials are not configured.');
                }

                Stripe::setApiKey($stripeSecret);

                $paymentIntent = PaymentIntent::create([
                    'amount' => (int) ($price * 100), // convert to cents
                    'currency' => strtolower($this->settingsRepository->get('platform.general.default_currency', 'INR')),
                    'payment_method_types' => ['card'],
                    'metadata' => [
                        'course_id' => $courseIdToUse,
                        'user_id' => $user->id,
                    ],
                ]);

                $enrollment = Enrollment::firstOrCreate([
                    'user_id' => $user->id,
                    'course_id' => $courseIdToUse,
                ], [
                    'status' => Enrollment::STATUS_PENDING,
                ]);

                $payment = Payment::create([
                    'enrollment_id' => $enrollment->id,
                    'bundle_id' => $isBundle ? $course->id : null,
                    'payment_method' => Payment::PAYMENT_METHOD_STRIPE,
                    'amount' => $price,
                    'status' => Payment::STATUS_PENDING,
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'stripe_client_secret' => $paymentIntent->client_secret,
                    'coupon_id' => $appliedCoupon?->id,
                    'discount_amount' => $discountAmount,
                ]);

                $enrollment->update(['payment_id' => $payment->id]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'payment_intent_id' => $paymentIntent->id,
                    'client_secret' => $paymentIntent->client_secret,
                    'publishable_key' => $credentials['key'] ?? '',
                    'enrollment_id' => $enrollment->id,
                    'bundle_id' => $isBundle ? $course->id : null,
                    'amount' => $price,
                ]);
            } elseif ($price > 0 && $paymentMethod === Payment::PAYMENT_METHOD_PESAPAL) {
                try {
                    $pesapalService = app(\App\Services\PesapalService::class);

                    $enrollment = Enrollment::firstOrCreate([
                        'user_id'   => $user->id,
                        'course_id' => $courseIdToUse,
                    ], [
                        'status' => Enrollment::STATUS_PENDING,
                    ]);

                    $merchantReference = 'PSP-C' . $courseIdToUse . '-U' . $user->id . '-' . time();
                    $pesapalOrder = $pesapalService->submitOrder([
                        'merchant_reference' => $merchantReference,
                        'currency'           => currency_code() ?: 'TZS',
                        'amount'             => $price,
                        'description'        => ($isBundle ? 'Bundle: ' : 'Course: ') . $course->title,
                        'callback_url'       => route('payment.pesapal.callback'),
                        'ipn_url'            => route('payment.pesapal.ipn'),
                        'customer_name'      => $user->name,
                        'customer_email'     => $user->email,
                        'customer_phone'     => $user->phone ?? '',
                    ]);

                    $payment = Payment::create([
                        'user_id'                    => $user->id,
                        'enrollment_id'              => $enrollment->id,
                        'bundle_id'                  => $isBundle ? $course->id : null,
                        'payment_method'             => Payment::PAYMENT_METHOD_PESAPAL,
                        'amount'                     => $price,
                        'status'                     => Payment::STATUS_PENDING,
                        'pesapal_order_tracking_id'  => $pesapalOrder['order_tracking_id'],
                        'pesapal_merchant_reference' => $pesapalOrder['merchant_reference'],
                        'coupon_id'                  => $appliedCoupon?->id,
                        'discount_amount'            => $discountAmount,
                    ]);

                    $enrollment->update(['payment_id' => $payment->id]);

                    DB::commit();

                    return response()->json([
                        'success'            => true,
                        'checkout_url'       => $pesapalOrder['redirect_url'],
                        'order_tracking_id'  => $pesapalOrder['order_tracking_id'],
                        'merchant_reference' => $pesapalOrder['merchant_reference'],
                        'enrollment_id'      => $enrollment->id,
                        'bundle_id'          => $isBundle ? $course->id : null,
                        'amount'             => $price,
                    ]);
                } catch (\Exception $e) {
                    throw new \Exception('Failed to create Pesapal session: ' . $e->getMessage());
                }
            } elseif ($price > 0 && $paymentMethod === Payment::PAYMENT_METHOD_PAYPAL) {
                try {
                    $paypalData = $this->createPaypalOrder($price, $course, $user);

                    $enrollment = Enrollment::firstOrCreate([
                        'user_id' => $user->id,
                        'course_id' => $courseIdToUse,
                    ], [
                        'status' => Enrollment::STATUS_PENDING,
                    ]);

                    $payment = Payment::create([
                        'enrollment_id' => $enrollment->id,
                        'bundle_id' => $isBundle ? $course->id : null,
                        'payment_method' => Payment::PAYMENT_METHOD_PAYPAL,
                        'amount' => $price,
                        'status' => Payment::STATUS_PENDING,
                        'paypal_order_id' => $paypalData['order_id'],
                        'coupon_id' => $appliedCoupon?->id,
                        'discount_amount' => $discountAmount,
                    ]);

                    $enrollment->update(['payment_id' => $payment->id]);

                    DB::commit();

                    $credentials = $this->paymentGatewayService->credentialsFor(Payment::PAYMENT_METHOD_PAYPAL);
                    return response()->json([
                        'success' => true,
                        'order_id' => $paypalData['order_id'],
                        'approve_url' => $paypalData['approve_url'],
                        'enrollment_id' => $enrollment->id,
                    'bundle_id' => $isBundle ? $course->id : null,
                        'amount' => $price,
                    ]);
                } catch (\Exception $e) {
                    throw new \Exception('Failed to create PayPal order: ' . $e->getMessage());
                }
            } elseif ($price > 0 && $paymentMethod === Payment::PAYMENT_METHOD_OFFLINE) {
                $receiptPath = null;
                if ($request->hasFile('receipt_file')) {
                    $receiptPath = $this->fileUploadService->uploadReceipt($request->file('receipt_file'));
                }

                $enrollment = Enrollment::firstOrCreate([
                    'user_id' => $user->id,
                    'course_id' => $courseIdToUse,
                ], [
                    'status' => Enrollment::STATUS_PENDING,
                ]);

                $payment = Payment::create([
                    'enrollment_id' => $enrollment->id,
                    'bundle_id' => $isBundle ? $course->id : null,
                    'payment_method' => Payment::PAYMENT_METHOD_OFFLINE,
                    'amount' => $price,
                    'status' => Payment::STATUS_PENDING,
                    'receipt_file' => $receiptPath,
                    'transaction_id' => $request->transaction_id,
                    'coupon_id' => $appliedCoupon?->id,
                    'discount_amount' => $discountAmount,
                ]);

                $enrollment->update(['payment_id' => $payment->id]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Your enrollment request has been submitted. Please wait for admin approval.',
                    'enrollment_id' => $enrollment->id,
                    'bundle_id' => $isBundle ? $course->id : null,
                ]);
            } elseif ($price == 0) {
                // Handle free course (or 100% coupon discounted)
                $enrollment = Enrollment::firstOrCreate([
                    'user_id' => $user->id,
                    'course_id' => $courseIdToUse,
                ], [
                    'status' => Enrollment::STATUS_APPROVED,
                    'enrolled_at' => now(),
                ]);

                // Record coupon usage if a coupon was applied
                if ($appliedCoupon) {
                    CouponUsage::create([
                        'coupon_id' => $appliedCoupon->id,
                        'user_id' => $user->id,
                        'enrollment_id' => $enrollment->id,
                        'discount_amount' => $discountAmount,
                    ]);
                    $appliedCoupon->increment('used_count');
                }

                DB::commit();

                $this->sendEnrollmentNotifications($enrollment, $course, $user);

                return response()->json([
                    'success' => true,
                    'message' => 'You have successfully enrolled in this course!',
                    'enrollment_id' => $enrollment->id,
                    'bundle_id' => $isBundle ? $course->id : null,
                ]);
            } else {
                // If we get here, it means price > 0 but no payment method matched
                throw new \Exception('Invalid payment method selected for paid course.');
            }
        } catch (\Exception $e) {
            Log::error('Enrollment failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'An error occurred during enrollment. Please try again.',
            ], 500);
        }
    }

    /**
     * Stripe verify.
     */
    public function stripeVerify(Request $request)
    {
        $request->validate([
            'payment_intent_id' => 'required|string',
            'enrollment_id' => 'required|exists:enrollments,id',
        ]);

        $enrollment = Enrollment::with('payment')->findOrFail($request->enrollment_id);

        if (!$enrollment->payment || $enrollment->payment->stripe_payment_intent_id !== $request->payment_intent_id) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payment details.'
            ], 400);
        }

        $credentials = $this->credentials('stripe');
        $stripeSecret = $credentials['secret'] ?? '';

        if (empty($stripeSecret)) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe credentials are not configured.'
            ], 500);
        }

        Stripe::setApiKey($stripeSecret);

        try {
            $paymentIntent = PaymentIntent::retrieve($request->payment_intent_id);

            if ($paymentIntent->status === 'succeeded') {
                DB::beginTransaction();
                try {
                    $enrollment->payment->update([
                        'status' => Payment::STATUS_COMPLETED,
                    ]);

                    $enrollment->update([
                        'status' => Enrollment::STATUS_APPROVED,
                        'enrolled_at' => now(),
                    ]);

                    $this->processRevenueShare($enrollment);

                    DB::commit();

                    $this->sendEnrollmentNotifications($enrollment);

                    $bundleData = $this->buildBundleCoursesData($enrollment->fresh('payment'));

                    return response()->json([
                        'success'        => true,
                        'message'        => 'Payment successful! You have been enrolled.',
                        'enrollment_id'  => $enrollment->id,
                        'bundle_id'      => $bundleData['bundle_id'] ?? null,
                        'bundle_title'   => $bundleData['bundle_title'] ?? null,
                        'bundle_courses' => $bundleData['courses'] ?? [],
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'An error occurred processing your payment.'
                    ], 500);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Stripe payment not succeeded. Status: ' . $paymentIntent->status,
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve Stripe payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate price.
     */
    private function calculatePrice(Course $course): float
    {
        if ($course->pricing_model === 'free') {
            return 0;
        }

        $rawPrice = $course->sale_price ? (float) $course->sale_price : (float) $course->regular_price;

        return $course->priceInCurrency($rawPrice, base_currency_code());
    }

    /**
     * Credentials.
     */
    private function credentials(string $identifier): array
    {
        return $this->paymentGatewayService->credentialsFor($identifier);
    }

    /**
     * Send enrollment notifications.
     */
    private function sendEnrollmentNotifications(Enrollment $enrollment, ?Course $course = null, ?User $student = null): void
    {
        try {
            $enrollment->loadMissing('course.instructor.notificationSettings', 'user.notificationSettings');

            $course = $course ?? $enrollment->course;
            $student = $student ?? $enrollment->user;
            $instructor = $course?->instructor;
            $enrolledAt = $enrollment->enrolled_at ?? now();

            if ($student && $student->email && $this->notificationPreferenceService->prefers($student, 'email_notifications')) {
                Mail::to($student->email)->send(new StudentCourseEnrollmentMail($course, $student, $enrolledAt));
            }

            if ($instructor && $instructor->email && $student && $this->notificationPreferenceService->prefers($instructor, 'course_updates')) {
                Mail::to($instructor->email)->send(new InstructorCourseEnrollmentMail($course, $student, $enrolledAt));
            }
        } catch (\Exception $e) {
            \Log::error('Enrollment email failed: ' . $e->getMessage());
        }
    }

    /**
     * Process revenue share.
     */
    private function processRevenueShare(Enrollment $enrollment): void
    {
        if (!$enrollment->payment_id) {
            return;
        }

        $payment = Payment::with('enrollment.course.instructor')->find($enrollment->payment_id);

        if (!$payment || $payment->status !== Payment::STATUS_COMPLETED) {
            return;
        }

        $this->revenueShareService->process($payment);
    }

    /**
     * Build bundle courses data for the success response.
     * Returns a flat array of course data if the enrollment was part of a bundle,
     * or an empty array for single-course enrollments.
     */
    private function buildBundleCoursesData(Enrollment $enrollment): array
    {
        if (!$enrollment->payment || !$enrollment->payment->bundle_id) {
            return [];
        }

        $bundle = \App\Models\Bundle::with('courses.instructor')
            ->find($enrollment->payment->bundle_id);

        if (!$bundle) {
            return [];
        }

        return [
            'bundle_id'    => $bundle->id,
            'bundle_title' => $bundle->title,
            'courses'      => $bundle->courses->map(function ($course) {
                return [
                    'id'              => $course->id,
                    'title'           => $course->title,
                    'thumbnail'       => $course->featured_image
                        ? asset('storage/' . $course->featured_image)
                        : null,
                    'instructor_name' => $course->instructor->name ?? null,
                    'rating'          => $course->rating ?? 0,
                    'students_count'  => $course->students_count ?? 0,
                ];
            })->values()->toArray(),
        ];
    }

    /**
     * Paypal verify.
     */
    public function paypalVerify(Request $request)
    {
        $request->validate([
            'order_id' => 'required|string',
            'enrollment_id' => 'required|exists:enrollments,id',
        ]);

        $enrollment = Enrollment::with('payment')->findOrFail($request->enrollment_id);

        if (!$enrollment->payment || $enrollment->payment->paypal_order_id !== $request->order_id) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payment details.'
            ], 400);
        }

        $accessToken = $this->getPaypalAccessToken();
        $credentials = $this->paymentGatewayService->credentialsFor('paypal');
        $mode = $credentials['mode'] ?? 'sandbox';
        $baseUrl = $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        DB::beginTransaction();

        try {
            // Capture the order
            $ch = curl_init($baseUrl . '/v2/checkout/orders/' . $request->order_id . '/capture');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 201) {
                $enrollment->payment->update([
                    'status' => Payment::STATUS_FAILED,
                ]);

                DB::commit();

                return response()->json([
                    'success' => false,
                    'message' => 'Payment capture failed.'
                ], 400);
            }

            $captureData = json_decode($response, true);

            if (!isset($captureData['status']) || $captureData['status'] !== 'COMPLETED') {
                $enrollment->payment->update([
                    'status' => Payment::STATUS_FAILED,
                ]);

                DB::commit();

                return response()->json([
                    'success' => false,
                    'message' => 'Payment was not successful.'
                ], 400);
            }

            $payerId = $captureData['payer']['payer_id'] ?? null;
            $paymentId = $captureData['purchase_units'][0]['payments']['captures'][0]['id'] ?? null;

            $enrollment->payment->update([
                'paypal_payer_id' => $payerId,
                'paypal_payment_id' => $paymentId,
                'status' => Payment::STATUS_COMPLETED,
            ]);

            $enrollment->update([
                'status' => Enrollment::STATUS_APPROVED,
                'enrolled_at' => now(),
            ]);

            $this->processRevenueShare($enrollment);

            DB::commit();

            $this->sendEnrollmentNotifications($enrollment);

            $bundleData = $this->buildBundleCoursesData($enrollment->fresh('payment'));

            return response()->json([
                'success'        => true,
                'message'        => 'Payment successful! You have been enrolled.',
                'enrollment_id'  => $enrollment->id,
                'bundle_id'      => $bundleData['bundle_id'] ?? null,
                'bundle_title'   => $bundleData['bundle_title'] ?? null,
                'bundle_courses' => $bundleData['courses'] ?? [],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An error occurred processing your payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get paypal access token.
     */
    private function getPaypalAccessToken(): string
    {
        $credentials = $this->paymentGatewayService->credentialsFor('paypal');
        $clientId = $credentials['client_id'] ?? null;
        $clientSecret = $credentials['client_secret'] ?? null;
        $mode = $credentials['mode'] ?? 'sandbox';

        if (!$clientId || !$clientSecret) {
            throw new \RuntimeException('PayPal credentials are not configured.');
        }

        $baseUrl = $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $ch = curl_init($baseUrl . '/v1/oauth2/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_USERPWD, $clientId . ':' . $clientSecret);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Accept-Language: en_US',
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception('Failed to get PayPal access token.');
        }

        $tokenData = json_decode($response, true);

        if (!isset($tokenData['access_token'])) {
            throw new \Exception('Invalid response from PayPal token endpoint.');
        }

        return $tokenData['access_token'];
    }

    /**
     * Create paypal order.
     */
    private function createPaypalOrder(float $amount, $course, $user): array
    {
        $courseIdToUse = $course->id;
        $accessToken = $this->getPaypalAccessToken();
        $credentials = $this->paymentGatewayService->credentialsFor('paypal');
        $mode = $credentials['mode'] ?? 'sandbox';
        $baseUrl = $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $orderData = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => 'course_' . $courseIdToUse . '_user_' . $user->id . '_' . time(),
                    'description' => 'Course Enrollment: ' . $course->title,
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => number_format($amount, 2, '.', ''),
                    ],
                ]
            ],
            'application_context' => [
                'brand_name' => config('app.name'),
                'landing_page' => 'BILLING',
                'user_action' => 'PAY_NOW',
                'return_url' => url('/payment/paypal/callback?success=true'),
                'cancel_url' => url('/payment/paypal/callback?success=false'),
            ],
        ];

        $ch = curl_init($baseUrl . '/v2/checkout/orders');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
            'PayPal-Request-Id: ' . uniqid(),
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 201) {
            throw new \Exception('Failed to create PayPal order.');
        }

        $orderResponse = json_decode($response, true);

        if (!isset($orderResponse['id']) || !isset($orderResponse['links'])) {
            throw new \Exception('Invalid response from PayPal.');
        }

        $approveUrl = null;
        foreach ($orderResponse['links'] as $link) {
            if ($link['rel'] === 'approve') {
                $approveUrl = $link['href'];
                break;
            }
        }

        if (!$approveUrl) {
            throw new \Exception('Approve URL not found in PayPal response.');
        }

        return [
            'order_id' => $orderResponse['id'],
            'approve_url' => $approveUrl,
        ];
    }

    /**
     * Verify a Pesapal API 3.0 payment and finalize enrollment for mobile/API clients.
     */
    public function pesapalVerify(Request $request)
    {
        $request->validate([
            'order_tracking_id' => 'required|string',
            'enrollment_id'     => 'required|exists:enrollments,id',
        ]);

        $enrollment = Enrollment::with('payment')->findOrFail($request->enrollment_id);

        if (! $enrollment->payment || $enrollment->payment->pesapal_order_tracking_id !== $request->order_tracking_id) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payment details.',
            ], 400);
        }

        try {
            $pesapalService = app(\App\Services\PesapalService::class);
            $statusData = $pesapalService->getTransactionStatus($request->order_tracking_id);

            if ($pesapalService->isCompletedStatus($statusData)) {
                $paidAmount = (float) ($statusData['amount'] ?? $enrollment->payment->amount);
                if (abs($paidAmount - (float) $enrollment->payment->amount) >= 0.01) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Pesapal paid amount mismatch.',
                    ], 400);
                }

                $alreadyCompleted = $enrollment->payment->status === Payment::STATUS_COMPLETED;
                $confirmationCode = (string) ($statusData['confirmation_code'] ?? '');
                $paymentAccount = (string) ($statusData['payment_account'] ?? '');

                DB::beginTransaction();
                try {
                    $enrollment->payment->update([
                        'pesapal_confirmation_code' => $confirmationCode !== '' ? $confirmationCode : null,
                        'pesapal_payment_account'   => $paymentAccount !== '' ? $paymentAccount : null,
                        'transaction_id'            => $confirmationCode !== '' ? $confirmationCode : $request->order_tracking_id,
                        'payment_date'              => $enrollment->payment->payment_date ?? now()->toDateString(),
                        'verification_status'       => 'verified',
                        'status'                    => Payment::STATUS_COMPLETED,
                    ]);

                    if (! $alreadyCompleted) {
                        $enrollment->update([
                            'status'      => Enrollment::STATUS_APPROVED,
                            'enrolled_at' => $enrollment->enrolled_at ?? now(),
                        ]);

                        if ($enrollment->payment->bundle_id) {
                            $bundle = \App\Models\Bundle::with('courses')->find($enrollment->payment->bundle_id);
                            if ($bundle) {
                                foreach ($bundle->courses as $bundleCourseItem) {
                                    if ((int) $bundleCourseItem->id === (int) $enrollment->course_id) {
                                        continue;
                                    }

                                    Enrollment::updateOrCreate(
                                        ['user_id' => $enrollment->user_id, 'course_id' => $bundleCourseItem->id],
                                        [
                                            'status'      => Enrollment::STATUS_APPROVED,
                                            'enrolled_at' => now(),
                                        ]
                                    );
                                }

                                \App\Models\BundleEnrollment::updateOrCreate(
                                    ['user_id' => $enrollment->user_id, 'bundle_id' => $bundle->id],
                                    [
                                        'status'      => 'approved',
                                        'enrolled_at' => now(),
                                    ]
                                );
                            }
                        }

                        $this->processRevenueShare($enrollment->fresh());
                    }

                    DB::commit();

                    if (! $alreadyCompleted) {
                        $this->sendEnrollmentNotifications($enrollment);
                    }

                    $bundleData = $this->buildBundleCoursesData($enrollment->fresh('payment'));

                    return response()->json([
                        'success'        => true,
                        'message'        => 'Payment successful! You have been enrolled.',
                        'enrollment_id'  => $enrollment->id,
                        'bundle_id'      => $bundleData['bundle_id'] ?? null,
                        'bundle_title'   => $bundleData['bundle_title'] ?? null,
                        'bundle_courses' => $bundleData['courses'] ?? [],
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => 'Error updating enrollment: ' . $e->getMessage(),
                    ], 500);
                }
            }

            if ($pesapalService->isFailedStatus($statusData)) {
                $enrollment->payment->update(['status' => Payment::STATUS_FAILED]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Pesapal payment status: ' . ($statusData['payment_status_description'] ?? 'pending'),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pesapal verification error: ' . $e->getMessage(),
            ], 500);
        }
    }

}
