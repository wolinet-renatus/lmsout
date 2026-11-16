<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\Payment;
use App\Services\FileUploadService;
use App\Services\PaymentGatewayService;
use App\Services\NotificationPreferenceService;
use App\Services\RevenueShareService;
use App\Services\SettingsRepository;
use App\Services\CouponService;
use App\Models\CouponUsage;
use App\Mail\StudentCourseEnrollmentMail;
use App\Mail\InstructorCourseEnrollmentMail;
use Illuminate\Support\Facades\Mail;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;

/**
 * Course Enrollment Controller
 *
 * This controller handles the course enrollment functionality.
 *
 * @package App\Http\Controllers\Front
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
        private NotificationPreferenceService $notificationPreferenceService,
        private RevenueShareService $revenueShareService,
        private SettingsRepository $settingsRepository,
        private CouponService $couponService
    ) {
        $this->fileUploadService = $fileUploadService;
        $this->paymentGatewayService = $paymentGatewayService;
    }

    /**
     * Display checkout page
     */
    public function checkoutBundle($id)
    {
        request()->merge(['type' => 'bundle']);
        return $this->checkout($id);
    }
    
    /**
     * Process checkout bundle.
     */
    public function processCheckoutBundle(CheckoutRequest $request, $id)
    {
        $request->merge(['type' => 'bundle']);
        return $this->processCheckout($request, $id);
    }

    /**
     * Checkout.
     */
    public function checkout($id)
    {
        $user = Auth::user();
        /** @var User $user */

        if (!$user) {
            ToastMagic::warning('Please login to enroll.');
            return redirect()->route('login');
        }

        if (!$user->isStudent()) {
            ToastMagic::error('Only students can checkout.');
            return redirect()->route('home');
        }

        $isBundle = request()->input('type') === 'bundle';
        $campusInvoice = null;

        if ($isBundle) {
            $course = \App\Models\Bundle::with('vendor', 'courses')->findOrFail($id);
            if ($course->status !== 'active' || $course->approval_status !== 'approved') {
                ToastMagic::error('This bundle is not available.');
                return redirect()->route('home');
            }

            $ownedCourseIds = Enrollment::where('user_id', $user->id)
                ->whereIn('status', ['approved', 'completed'])
                ->pluck('course_id')
                ->toArray();

            $bundleCourseIds = $course->courses->pluck('id')->toArray();
            if (count(array_intersect($ownedCourseIds, $bundleCourseIds)) > 0) {
                ToastMagic::info('You already own some courses in this bundle. Proceeding will enroll you in the remaining courses and charge the full bundle price.');
            }
            $price = (float) $course->price;
        } else {
            if (request()->filled('invoice_id')) {
                $campusInvoice = \App\Models\Invoice::withoutGlobalScope('branch')
                    ->with('paymentSchedules')
                    ->find(request()->integer('invoice_id'));

                if (! $campusInvoice) {
                    ToastMagic::error('The requested invoice could not be found.');
                    return redirect()->route('student.campus.invoices.index');
                }

                if ((int) $campusInvoice->user_id !== (int) $user->id) {
                    ToastMagic::error('You do not have permission to pay this invoice.');
                    return redirect()->route('student.campus.invoices.index');
                }

                if ($campusInvoice->course_id && (int) $campusInvoice->course_id !== (int) $id) {
                    return redirect()->route('courses.checkout', array_filter([
                        'id'         => $campusInvoice->course_id,
                        'invoice_id' => $campusInvoice->id,
                        'pay'        => request()->input('pay'),
                    ]));
                }

                $course = Course::with('instructor')->findOrFail($campusInvoice->course_id ?? $id);

                if ($course->status !== 'published' || $course->visibility !== 'public') {
                    ToastMagic::error('This course is not available for enrollment.');
                    return redirect()->route('courses.show', $course->id);
                }

                if ($campusInvoice->balance <= 0) {
                    ToastMagic::info('This invoice is already fully paid.');
                    return redirect()->route('student.campus.invoices.index');
                }
                if (! $course->allow_online_f2f_payment) {
                    ToastMagic::warning('Online payment is disabled for this course. Please pay using Control Number ' . $campusInvoice->control_number);
                    return redirect()->route('courses.apply.confirmation', $campusInvoice->id);
                }

                $nextSched = $campusInvoice->paymentSchedules->where('status', '!=', 'paid')->sortBy('installment_number')->first();
                $price = (request()->input('pay') === 'balance' || ! $nextSched)
                    ? (float) $campusInvoice->balance
                    : round((float) $nextSched->amount_due - (float) $nextSched->amount_paid, 2);
            } else {
                $course = Course::with('instructor')->findOrFail($id);

                if ($course->status !== 'published' || $course->visibility !== 'public') {
                    ToastMagic::error('This course is not available for enrollment.');
                    return redirect()->route('courses.show', $course->id);
                }

                if ($course->requiresApplicantForm()) {
                    return redirect()->route('courses.apply', $course->id);
                }

                $existingEnrollment = Enrollment::with('payment')
                    ->where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->first();

                if ($existingEnrollment) {
                    if (in_array($existingEnrollment->status, [Enrollment::STATUS_APPROVED, Enrollment::STATUS_COMPLETED], true)) {
                        ToastMagic::info('You are already enrolled in this course.');
                        return redirect()->route('courses.show', $course->id);
                    }

                    if ($existingEnrollment->payment && $existingEnrollment->payment->payment_method === Payment::PAYMENT_METHOD_OFFLINE) {
                        ToastMagic::warning('You have a pending offline payment request. Please wait for admin approval.');
                        return redirect()->route('courses.show', $course->id);
                    }
                }

                $price = $this->calculatePrice($course);

                if ($price <= 0) {
                    $enrollment = Enrollment::create([
                        'user_id' => $user->id,
                        'course_id' => $course->id,
                        'status' => Enrollment::STATUS_APPROVED,
                        'enrolled_at' => now(),
                    ]);

                    ToastMagic::success('Congratulations! You have successfully enrolled in this free course.');
                    return redirect()->route('student.courses.show', $course->id);
                }
            }
        }

        $onlineGateways = $this->paymentGatewayService->onlineGateways();
        // Online courses use direct online payment only (no manual offline checkout)
        $offlineGateway = (! $isBundle && $course->isOnline()) ? null : $this->paymentGatewayService->offlineGateway();
        $offlineInstructions = $offlineGateway && $offlineGateway->is_enabled ? $offlineGateway->offline_instructions : null;

        $gatewayDescriptions = [
            'stripe'      => 'Pay securely with credit/debit card via Stripe.',
            'flutterwave' => 'Pay securely with multiple methods via Flutterwave.',
            'paypal'      => 'Pay securely with PayPal account or credit/debit card.',
            'pesapal'     => 'Pay securely with M-Pesa, Tigo Pesa, Airtel Money, HaloPesa, or card via Pesapal.',
        ];

        return view('checkout', compact('course', 'price', 'onlineGateways', 'offlineGateway', 'offlineInstructions', 'gatewayDescriptions', 'isBundle', 'campusInvoice'));
    }

    /**
     * Process checkout
     */
    public function processCheckout(CheckoutRequest $request, $id)
    {
        $user = Auth::user();
        /** @var User $user */

        $isBundle = request()->input('type') === 'bundle';
        
        if ($isBundle) {
            $course = \App\Models\Bundle::with('courses')->findOrFail($id);
            if ($course->status !== 'active' || $course->approval_status !== 'approved') {
                ToastMagic::error('This bundle is not available.');
                return redirect()->route('home');
            }

            $ownedCourseIds = Enrollment::where('user_id', $user->id)
                ->whereIn('status', ['approved', 'completed'])
                ->pluck('course_id')
                ->toArray();

            $bundleCourseIds = $course->courses->pluck('id')->toArray();
            if (count(array_intersect($ownedCourseIds, $bundleCourseIds)) > 0) {
                // Wait for the duplicate flag in realistic scenarios, but let's just proceed
            }
            
            $unownedCourses = $course->courses->whereNotIn('id', $ownedCourseIds);
            if ($unownedCourses->isEmpty()) {
                ToastMagic::error('You already own all the courses in this bundle.');
                return redirect()->route('home');
            }

            $price = (float) $course->price;
            $courseIdToUse = $unownedCourses->first()->id;
        } else {
            $course = Course::findOrFail($id);

            if ($course->status !== 'published' || $course->visibility !== 'public') {
                ToastMagic::error('This course is not available for enrollment.');
                return redirect()->route('courses.show', $course->id);
            }

            if ($request->filled('invoice_id')) {
                $campusInvoice = \App\Models\Invoice::withoutGlobalScope('branch')
                    ->with('paymentSchedules')
                    ->find($request->integer('invoice_id'));

                if (! $campusInvoice || (int) $campusInvoice->user_id !== (int) $user->id || ($campusInvoice->course_id && (int) $campusInvoice->course_id !== (int) $course->id)) {
                    if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => 'Invoice could not be matched to this course.'], 422);
                    }
                    ToastMagic::error('Invoice could not be matched to this course.');
                    return redirect()->route('student.campus.invoices.index');
                }

                if ($campusInvoice->balance <= 0) {
                    if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => 'This invoice is already fully paid.'], 422);
                    }
                    ToastMagic::info('This invoice is already fully paid.');
                    return redirect()->route('student.campus.invoices.index');
                }

                if (strtolower((string) $request->payment_method) !== Payment::PAYMENT_METHOD_OFFLINE && ! $course->allow_online_f2f_payment) {
                    if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => 'Online payment is disabled for this course. Please use Control Number ' . $campusInvoice->control_number . '.'], 422);
                    }
                    ToastMagic::warning('Online payment is disabled for this course.');
                    return redirect()->route('courses.apply.confirmation', $campusInvoice->id);
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
                return redirect()->route('courses.apply', $course->id);
            } else {
                if (strtolower($request->payment_method) === Payment::PAYMENT_METHOD_OFFLINE && $course->isOnline()) {
                    ToastMagic::error('Online courses require direct online payment.');
                    return redirect()->back()->withInput();
                }

                $existingEnrollment = Enrollment::where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->first();

                if ($existingEnrollment) {
                    if (
                        $existingEnrollment->status === Enrollment::STATUS_APPROVED ||
                        $existingEnrollment->status === Enrollment::STATUS_COMPLETED
                    ) {
                        ToastMagic::info('You are already enrolled in this course.');
                        return redirect()->route('courses.show', $course->id);
                    }

                    $payment = $existingEnrollment->payment;
                    if ($payment && $payment->payment_method === Payment::PAYMENT_METHOD_OFFLINE) {
                        ToastMagic::warning('You have a pending offline payment request. Please wait for admin approval.');
                        return redirect()->route('courses.show', $course->id);
                    }

                    $existingEnrollment->delete();
                }

                $price = $this->calculatePrice($course);

                if ($price <= 0) {
                    $enrollment = Enrollment::create([
                        'user_id' => $user->id,
                        'course_id' => $course->id,
                        'status' => Enrollment::STATUS_APPROVED,
                        'enrolled_at' => now(),
                    ]);

                    ToastMagic::success('Congratulations! You have successfully enrolled in this free course.');
                    return redirect()->route('student.courses.show', $course->id);
                }
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
                if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
                }
                ToastMagic::error($e->getMessage());
                return redirect()->back()->withInput();
            }
        }

        $subtotal = $price;
        $taxAmount = 0.0;
        $taxRate = 0.0;
        if (tax_enabled() && $price > 0) {
            $taxRate = tax_rate();
            $taxAmount = round(($subtotal * $taxRate) / 100, 2);
            $price = $subtotal + $taxAmount;
        }

        if ($price > 0 && !$this->paymentGatewayService->isEnabled($paymentMethod)) {
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Selected payment method is not available.'], 422);
            }
            ToastMagic::error('Selected payment method is not available.');
            return redirect()->back()->withInput();
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
                $stripeCheckoutSession = $this->createStripeCheckoutSession($price, $course, $user->id);

                $enrollment = Enrollment::firstOrCreate([
                    'user_id' => $user->id,
                    'course_id' => $courseIdToUse,
                ], [
                    'status' => Enrollment::STATUS_PENDING,
                ]);

                $payment = Payment::create([
                    'user_id' => $user->id,
                    'enrollment_id' => $enrollment->id,
                    'bundle_id' => $isBundle ? $course->id : null,
                    'payment_method' => Payment::PAYMENT_METHOD_STRIPE,
                    'amount' => $price,
                    'status' => Payment::STATUS_PENDING,
                    'verification_status' => 'pending',
                    'stripe_payment_intent_id' => $stripeCheckoutSession['session_id'],
                    'coupon_id' => $appliedCoupon?->id,
                    'discount_amount' => $discountAmount,
                ]);

                $enrollment->update(['payment_id' => $payment->id]);
                app(\App\Services\SrsWorkflowService::class)->ensureInvoiceForPayment($payment);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'session_id' => $stripeCheckoutSession['session_id'],
                    'checkout_url' => $stripeCheckoutSession['url'],
                ]);
            } elseif ($price > 0 && $paymentMethod === Payment::PAYMENT_METHOD_FLUTTERWAVE) {
                $flutterwaveTransaction = $this->createFlutterwaveTransaction($price, $course, $user);

                $enrollment = Enrollment::firstOrCreate([
                    'user_id' => $user->id,
                    'course_id' => $courseIdToUse,
                ], [
                    'status' => Enrollment::STATUS_PENDING,
                ]);

                $payment = Payment::create([
                    'user_id' => $user->id,
                    'enrollment_id' => $enrollment->id,
                    'bundle_id' => $isBundle ? $course->id : null,
                    'payment_method' => Payment::PAYMENT_METHOD_FLUTTERWAVE,
                    'amount' => $price,
                    'status' => Payment::STATUS_PENDING,
                    'verification_status' => 'pending',
                    'flutterwave_tx_ref' => $flutterwaveTransaction['tx_ref'],
                    'coupon_id' => $appliedCoupon?->id,
                    'discount_amount' => $discountAmount,
                ]);

                $enrollment->update(['payment_id' => $payment->id]);
                app(\App\Services\SrsWorkflowService::class)->ensureInvoiceForPayment($payment);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'checkout_url' => $flutterwaveTransaction['link'],
                    'tx_ref' => $flutterwaveTransaction['tx_ref'],
                ]);
            } elseif ($price > 0 && $paymentMethod === Payment::PAYMENT_METHOD_PAYPAL) {
                $paypalOrder = $this->createPaypalOrder($price, $course, $user);

                $enrollment = Enrollment::firstOrCreate([
                    'user_id' => $user->id,
                    'course_id' => $courseIdToUse,
                ], [
                    'status' => Enrollment::STATUS_PENDING,
                ]);

                $payment = Payment::create([
                    'user_id' => $user->id,
                    'enrollment_id' => $enrollment->id,
                    'bundle_id' => $isBundle ? $course->id : null,
                    'payment_method' => Payment::PAYMENT_METHOD_PAYPAL,
                    'amount' => $price,
                    'status' => Payment::STATUS_PENDING,
                    'verification_status' => 'pending',
                    'paypal_order_id' => $paypalOrder['order_id'],
                    'coupon_id' => $appliedCoupon?->id,
                    'discount_amount' => $discountAmount,
                ]);

                $enrollment->update(['payment_id' => $payment->id]);
                app(\App\Services\SrsWorkflowService::class)->ensureInvoiceForPayment($payment);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'checkout_url' => $paypalOrder['approve_url'],
                    'order_id' => $paypalOrder['order_id'],
                ]);
            } elseif ($price > 0 && $paymentMethod === Payment::PAYMENT_METHOD_PESAPAL) {
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
                    'currency'           => base_currency_code() ?: 'TZS',
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
                    'verification_status'        => 'pending',
                    'pesapal_order_tracking_id'  => $pesapalOrder['order_tracking_id'],
                    'pesapal_merchant_reference' => $pesapalOrder['merchant_reference'],
                    'coupon_id'                  => $appliedCoupon?->id,
                    'discount_amount'            => $discountAmount,
                ]);

                $enrollment->update(['payment_id' => $payment->id]);
                app(\App\Services\SrsWorkflowService::class)->ensureInvoiceForPayment($payment);

                DB::commit();

                return response()->json([
                    'success'            => true,
                    'checkout_url'       => $pesapalOrder['redirect_url'],
                    'order_tracking_id'  => $pesapalOrder['order_tracking_id'],
                    'merchant_reference' => $pesapalOrder['merchant_reference'],
                    'enrollment_id'      => $enrollment->id,
                    'bundle_id'          => $isBundle ? $course->id : null,
                ]);
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
                    'user_id' => $user->id,
                    'enrollment_id' => $enrollment->id,
                    'bundle_id' => $isBundle ? $course->id : null,
                    'payment_method' => Payment::PAYMENT_METHOD_OFFLINE,
                    'amount' => $price,
                    'status' => Payment::STATUS_PENDING,
                    'verification_status' => 'pending',
                    'receipt_file' => $receiptPath,
                    'transaction_id' => $request->transaction_id,
                    'coupon_id' => $appliedCoupon?->id,
                    'discount_amount' => $discountAmount,
                ]);

                $enrollment->update(['payment_id' => $payment->id]);
                app(\App\Services\SrsWorkflowService::class)->ensureInvoiceForPayment($payment);

                DB::commit();

                ToastMagic::success('Your enrollment request has been submitted. Please wait for admin approval.');
                return redirect()->route('enrollment.success', $enrollment->id);
            } elseif ($price == 0) {
                $enrollment = Enrollment::create([
                    'user_id' => $user->id,
                    'course_id' => $courseIdToUse,
                    'status' => Enrollment::STATUS_APPROVED,
                    'enrolled_at' => now(),
                ]);

                if ($appliedCoupon) {
                    CouponUsage::create([
                        'coupon_id' => $appliedCoupon->id,
                        'user_id' => $user->id,
                        'enrollment_id' => $enrollment->id,
                        'discount_amount' => $discountAmount,
                    ]);
                    $appliedCoupon->increment('used_count');
                }

                if ($isBundle) {
                    // Also approve all other courses in the bundle
                    foreach ($course->courses as $bundleCourseItem) {
                        if ($bundleCourseItem->id == $courseIdToUse) continue;
                        
                        Enrollment::updateOrCreate(
                            ['user_id' => $user->id, 'course_id' => $bundleCourseItem->id],
                            [
                                'status' => Enrollment::STATUS_APPROVED,
                                'enrolled_at' => now(),
                            ]
                        );
                    }

                    // Create BundleEnrollment record
                    \App\Models\BundleEnrollment::updateOrCreate(
                        ['user_id' => $user->id, 'bundle_id' => $course->id],
                        [
                            'status' => 'approved',
                            'enrolled_at' => now(),
                        ]
                    );
                }

                DB::commit();

                $this->sendEnrollmentNotifications($enrollment, $course, $user);

                ToastMagic::success('You have successfully enrolled in this course!');
                return redirect()->route('enrollment.success', $enrollment->id);
            } else {
                throw new \Exception('Invalid payment method selected for paid course.');
            }
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'An error occurred during enrollment. Please try again.',
                ], 500);
            }

            ToastMagic::error('An error occurred during enrollment. Please try again.');
            return redirect()->back()->withInput();
        }
    }

    /**
     * Display success page
     */
    public function success($id)
    {
        $enrollment = Enrollment::with(['course.instructor', 'payment.bundle.courses.instructor'])->findOrFail($id);

        if ($enrollment->user_id !== Auth::id()) {
            ToastMagic::error('Unauthorized access.');
            return redirect()->route('home');
        }

        // Determine if this was a bundle purchase and load the bundle's courses
        $bundle = null;
        $bundleCourses = collect();
        if ($enrollment->payment && $enrollment->payment->bundle_id) {
            $bundle = $enrollment->payment->bundle;
            $bundleCourses = $bundle ? $bundle->courses : collect();
        }

        return view('enrollment-success', compact('enrollment', 'bundle', 'bundleCourses'));
    }

    /**
     * Calculate course price in the platform base currency.
     */
    private function calculatePrice(Course $course): float
    {
        if ($course->pricing_model === 'free') {
            return 0;
        }

        $rawPrice = $course->sale_price ? (float) $course->sale_price : (float) $course->regular_price;

        return convert_currency($rawPrice, $course->getPriceCurrencyCode(), base_currency_code());
    }

    /**
     * Create Stripe checkout session
     */
    private function createStripeCheckoutSession(float $amount, $course, int $userId): array
    {
        $courseIdToUse = $course->id;
        $apiSecret = $this->credentials('stripe')['secret'] ?? null;

        if (!$apiSecret) {
            throw new \RuntimeException('Stripe secret is not configured.');
        }

        Stripe::setApiKey($apiSecret);

        try {
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'usd',
                            'product_data' => [
                                'name' => $course->title,
                                'description' => 'Course Enrollment',
                            ],
                            'unit_amount' => (int) ($amount * 100),
                        ],
                        'quantity' => 1,
                    ]
                ],
                'mode' => 'payment',
                'success_url' => route('payment.stripe.callback') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('payment.stripe.callback') . '?session_id={CHECKOUT_SESSION_ID}&canceled=true',
                'metadata' => [
                    'course_id' => $courseIdToUse,
                    'user_id' => $userId,
                ],
            ]);

            return [
                'session_id' => $session->id,
                'url' => $session->url,
            ];
        } catch (ApiErrorException $e) {
            throw new \Exception('Failed to create Stripe checkout session: ' . $e->getMessage());
        }
    }

    /**
     * Handle Stripe payment callback
     */
    public function stripeCallback(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $apiSecret = $this->credentials('stripe')['secret'] ?? null;
        if (!$apiSecret) {
            ToastMagic::error('Stripe configuration error.');
            return redirect()->route('courses');
        }

        Stripe::setApiKey($apiSecret);

        try {
            $session = Session::retrieve($request->session_id);

            if (!$session->metadata) {
                ToastMagic::error('Invalid payment session.');
                return redirect()->route('courses');
            }

            $courseId = $session->metadata['course_id'] ?? ($session->metadata->course_id ?? null);
            $userId = $session->metadata['user_id'] ?? ($session->metadata->user_id ?? null);

            if (!$courseId || !$userId) {
                ToastMagic::error('Invalid payment session metadata.');
                return redirect()->route('courses');
            }

            $enrollment = Enrollment::with('payment')
                ->where('user_id', $userId)
                ->where('course_id', $courseId)
                ->whereHas('payment', function ($query) use ($session) {
                    $query->where('stripe_payment_intent_id', $session->id);
                })
                ->first();

            if (!$enrollment || !$enrollment->payment) {
                ToastMagic::error('Enrollment not found.');
                return redirect()->route('courses.show', $courseId);
            }

            DB::beginTransaction();

            if ($request->has('canceled') && $request->canceled === 'true') {
                $enrollment->payment->update([
                    'status' => Payment::STATUS_FAILED,
                ]);

                DB::commit();

                ToastMagic::error('Payment was canceled.');
                return redirect()->route('courses.show', $courseId);
            }

            if ($session->payment_status === 'paid') {
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

                ToastMagic::success('Payment successful! You have been enrolled in the course.');
                return redirect()->route('enrollment.success', $enrollment->id);
            } else {
                $enrollment->payment->update([
                    'status' => Payment::STATUS_FAILED,
                ]);

                DB::commit();

                ToastMagic::error('Payment was not successful. Please try again.');
                return redirect()->route('courses.show', $courseId);
            }
        } catch (ApiErrorException $e) {
            DB::rollBack();
            ToastMagic::error('An error occurred processing your payment. Please contact support.');
            return redirect()->route('courses');
        } catch (\Exception $e) {
            DB::rollBack();
            ToastMagic::error('An error occurred processing your payment. Please contact support.');
            return redirect()->route('courses');
        }
    }

    /**
     * Create Flutterwave transaction
     */
    private function createFlutterwaveTransaction(float $amount, $course, $user): array
    {
        $courseIdToUse = $course->id;
        $credentials = $this->credentials('flutterwave');
        $publicKey = $credentials['public_key'] ?? null;
        $secretKey = $credentials['secret_key'] ?? null;

        if (!$publicKey || !$secretKey) {
            throw new \RuntimeException('Flutterwave credentials are not configured.');
        }

        $txRef = 'course_' . $courseIdToUse . '_user_' . $user->id . '_' . time();
        $amountInNaira = (float) $amount;

        $data = [
            'tx_ref' => $txRef,
            'amount' => $amountInNaira,
            'currency' => 'NGN',
            'redirect_url' => route('payment.flutterwave.callback'),
            'payment_options' => 'card,account,ussd,transfer',
            'customer' => [
                'email' => $user->email,
                'name' => $user->name,
                'phone_number' => $user->phone ?? '',
            ],
            'customizations' => [
                'title' => 'Course Enrollment',
                'description' => 'Course Enrollment: ' . $course->title,
            ],
            'meta' => [
                'course_id' => $courseIdToUse,
                'user_id' => $user->id,
            ],
        ];

        $ch = curl_init('https://api.flutterwave.com/v3/payments');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception('Failed to create Flutterwave transaction.');
        }

        $responseData = json_decode($response, true);

        if (!isset($responseData['status']) || $responseData['status'] !== 'success' || !isset($responseData['data']['link'])) {
            throw new \Exception('Invalid response from Flutterwave.');
        }

        return [
            'tx_ref' => $txRef,
            'link' => $responseData['data']['link'],
        ];
    }

    /**
     * Handle Flutterwave payment callback
     */
    public function flutterwaveCallback(Request $request)
    {
        $request->validate([
            'transaction_id' => 'nullable|string',
            'tx_ref' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $secretKey = $this->credentials('flutterwave')['secret_key'] ?? null;
        if (!$secretKey) {
            ToastMagic::error('Flutterwave configuration error.');
            return redirect()->route('courses');
        }

        try {
            $transactionId = $request->transaction_id;
            $txRef = $request->tx_ref;

            if (!$transactionId && !$txRef) {
                ToastMagic::error('Invalid payment callback.');
                return redirect()->route('courses');
            }

            $verifyUrl = $transactionId
                ? 'https://api.flutterwave.com/v3/transactions/' . $transactionId . '/verify'
                : 'https://api.flutterwave.com/v3/transactions/verify_by_reference?tx_ref=' . $txRef;

            $ch = curl_init($verifyUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $secretKey,
                'Content-Type: application/json',
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                throw new \Exception('Failed to verify Flutterwave transaction.');
            }

            $responseData = json_decode($response, true);

            if (!isset($responseData['status']) || $responseData['status'] !== 'success' || !isset($responseData['data'])) {
                ToastMagic::error('Invalid payment transaction.');
                return redirect()->route('courses');
            }

            $transactionData = $responseData['data'];
            $txRefFromResponse = $transactionData['tx_ref'] ?? $txRef;

            $meta = $transactionData['meta'] ?? null;

            if (!$meta || !isset($meta['course_id']) || !isset($meta['user_id'])) {
                ToastMagic::error('Invalid payment transaction metadata.');
                return redirect()->route('courses');
            }

            $courseId = $meta['course_id'];
            $userId = $meta['user_id'];
            $txRefFromResponse = $transactionData['tx_ref'] ?? $txRef;

            $enrollment = Enrollment::with('payment')
                ->where('user_id', $userId)
                ->where('course_id', $courseId)
                ->whereHas('payment', function ($query) use ($txRefFromResponse) {
                    $query->where('flutterwave_tx_ref', $txRefFromResponse);
                })
                ->first();

            if (!$enrollment || !$enrollment->payment) {
                ToastMagic::error('Enrollment not found.');
                return redirect()->route('courses.show', $courseId);
            }

            DB::beginTransaction();

            $transactionAmount = (float) ($transactionData['amount'] ?? 0);

            if ($transactionData['status'] === 'successful' && abs($transactionAmount - $enrollment->payment->amount) < 0.01) {
                $enrollment->payment->update([
                    'flutterwave_transaction_id' => $transactionData['id'] ?? null,
                    'status' => Payment::STATUS_COMPLETED,
                ]);

                $enrollment->update([
                    'status' => Enrollment::STATUS_APPROVED,
                    'enrolled_at' => now(),
                ]);

                $this->processRevenueShare($enrollment);

                DB::commit();

                $this->sendEnrollmentNotifications($enrollment);

                ToastMagic::success('Payment successful! You have been enrolled in the course.');
                return redirect()->route('enrollment.success', $enrollment->id);
            } else {
                $enrollment->payment->update([
                    'flutterwave_transaction_id' => $transactionData['id'] ?? null,
                    'status' => Payment::STATUS_FAILED,
                ]);

                DB::commit();

                ToastMagic::error('Payment was not successful. Please try again.');
                return redirect()->route('courses.show', $courseId);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            ToastMagic::error('An error occurred processing your payment. Please contact support.');
            return redirect()->route('courses');
        }
    }

    /**
     * Get PayPal OAuth access token
     */
    private function getPaypalAccessToken(): string
    {
        $credentials = $this->credentials('paypal');
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
     * Create PayPal order
     */
    private function createPaypalOrder(float $amount, $course, $user): array
    {
        $courseIdToUse = $course->id;
        $accessToken = $this->getPaypalAccessToken();
        $mode = $this->credentials('paypal')['mode'] ?? 'sandbox';
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
                'return_url' => route('payment.paypal.callback', ['success' => true]),
                'cancel_url' => route('payment.paypal.callback', ['success' => false]),
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
     * Handle PayPal payment callback
     */
    public function paypalCallback(Request $request)
    {
        $request->validate([
            'token' => 'nullable|string',
            'PayerID' => 'nullable|string',
        ]);

        if ($request->has('success') && $request->success === 'false') {
            ToastMagic::error('Payment was canceled.');
            return redirect()->route('courses');
        }

        $accessToken = $this->getPaypalAccessToken();
        $mode = $this->credentials('paypal')['mode'] ?? 'sandbox';
        $baseUrl = $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $orderId = $request->token;

        if (!$orderId) {
            ToastMagic::error('Invalid payment callback.');
            return redirect()->route('courses');
        }

        try {
            $ch = curl_init($baseUrl . '/v2/checkout/orders/' . $orderId);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                throw new \Exception('Failed to get PayPal order details.');
            }

            $orderData = json_decode($response, true);

            if (!isset($orderData['id']) || !isset($orderData['purchase_units'])) {
                ToastMagic::error('Invalid PayPal order.');
                return redirect()->route('courses');
            }

            $referenceId = $orderData['purchase_units'][0]['reference_id'] ?? '';
            preg_match('/course_(\d+)_user_(\d+)_/', $referenceId, $matches);

            if (count($matches) !== 3) {
                ToastMagic::error('Invalid order reference.');
                return redirect()->route('courses');
            }

            $courseId = (int) $matches[1];
            $userId = (int) $matches[2];

            $enrollment = Enrollment::with('payment')
                ->where('user_id', $userId)
                ->where('course_id', $courseId)
                ->whereHas('payment', function ($query) use ($orderId) {
                    $query->where('paypal_order_id', $orderId);
                })
                ->first();

            if (!$enrollment || !$enrollment->payment) {
                ToastMagic::error('Enrollment not found.');
                return redirect()->route('courses.show', $courseId);
            }

            DB::beginTransaction();

            $ch = curl_init($baseUrl . '/v2/checkout/orders/' . $orderId . '/capture');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 201) {
                $enrollment->payment->update([
                    'status' => Payment::STATUS_FAILED,
                ]);

                DB::commit();

                ToastMagic::error('Payment capture failed. Please try again.');
                return redirect()->route('courses.show', $courseId);
            }

            $captureData = json_decode($response, true);

            if (!isset($captureData['status']) || $captureData['status'] !== 'COMPLETED') {
                $enrollment->payment->update([
                    'status' => Payment::STATUS_FAILED,
                ]);

                DB::commit();

                ToastMagic::error('Payment was not successful. Please try again.');
                return redirect()->route('courses.show', $courseId);
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

            ToastMagic::success('Payment successful! You have been enrolled in the course.');
            return redirect()->route('enrollment.success', $enrollment->id);
        } catch (\Exception $e) {
            DB::rollBack();
            ToastMagic::error('An error occurred processing your payment. Please contact support.');
            return redirect()->route('courses');
        }
    }

    /**
     * Credentials.
     */
    private function credentials(string $identifier): array
    {
        return $this->paymentGatewayService->credentialsFor($identifier);
    }

    /**
     * Handle Pesapal browser redirect callback after customer checkout.
     */
    public function pesapalCallback(Request $request)
    {
        $orderTrackingId = trim((string) ($request->input('OrderTrackingId')
            ?? $request->query('OrderTrackingId')
            ?? $request->input('orderTrackingId')
            ?? $request->query('orderTrackingId', '')));
        $merchantReference = trim((string) ($request->input('OrderMerchantReference')
            ?? $request->query('OrderMerchantReference')
            ?? $request->input('orderMerchantReference')
            ?? $request->query('orderMerchantReference', '')));

        if ($orderTrackingId === '' && $merchantReference === '') {
            ToastMagic::error('Invalid Pesapal payment callback — missing OrderTrackingId.');
            return redirect()->route('courses');
        }

        $payment = $this->findPesapalPayment($orderTrackingId, $merchantReference);

        if (! $payment) {
            ToastMagic::error('Pesapal payment record not found.');
            return redirect()->route('courses');
        }

        $trackingLookup = $orderTrackingId !== '' ? $orderTrackingId : (string) $payment->pesapal_order_tracking_id;

        try {
            if ($payment->status === Payment::STATUS_COMPLETED) {
                $enrollment = $this->completePesapalPayment($payment, []);
                return $this->redirectAfterPesapalCompletion($payment, $enrollment);
            }

            $pesapalService = app(\App\Services\PesapalService::class);
            $statusData = $pesapalService->getTransactionStatus($trackingLookup);

            if ($pesapalService->isCompletedStatus($statusData)) {
                $paidAmount = (float) ($statusData['amount'] ?? $payment->amount);
                if (abs($paidAmount - (float) $payment->amount) >= 0.01) {
                    throw new \RuntimeException('Pesapal paid amount mismatch.');
                }

                $enrollment = $this->completePesapalPayment($payment, $statusData);

                return $this->redirectAfterPesapalCompletion($payment, $enrollment);
            }

            if ($pesapalService->isFailedStatus($statusData)) {
                $payment->update([
                    'status' => Payment::STATUS_FAILED,
                    'verification_status' => 'rejected',
                ]);
                ToastMagic::error('Pesapal payment was not completed (' . ($statusData['payment_status_description'] ?? 'Failed') . ').');

                return $payment->enrollment?->course_id
                    ? redirect()->route('courses.show', $payment->enrollment->course_id)
                    : redirect()->route('courses');
            }

            ToastMagic::warning('Pesapal payment is still pending confirmation.');

            return $payment->enrollment?->course_id
                ? redirect()->route('courses.show', $payment->enrollment->course_id)
                : redirect()->route('courses');
        } catch (\Throwable $e) {
            ToastMagic::error('Unable to verify Pesapal payment: ' . $e->getMessage());

            return redirect()->route('courses');
        }
    }

    /**
     * Poll Pesapal payment status asynchronously while checkout iframe is open.
     */
    public function pesapalStatus(Request $request)
    {
        $orderTrackingId = trim((string) ($request->query('order_tracking_id')
            ?? $request->query('OrderTrackingId', '')));
        $merchantReference = trim((string) ($request->query('merchant_reference')
            ?? $request->query('OrderMerchantReference', '')));

        if ($orderTrackingId === '' && $merchantReference === '') {
            return response()->json(['status' => 'invalid'], 422);
        }

        $payment = $this->findPesapalPayment($orderTrackingId, $merchantReference);
        if (! $payment) {
            return response()->json(['status' => 'not_found'], 404);
        }

        if ($payment->status === Payment::STATUS_COMPLETED) {
            $enrollment = $this->completePesapalPayment($payment, []);
            return response()->json([
                'status' => 'completed',
                'redirect_url' => $enrollment
                    ? route('enrollment.success', $enrollment->id)
                    : route('student.campus.invoices.index'),
            ]);
        }

        try {
            $pesapalService = app(\App\Services\PesapalService::class);
            $trackingLookup = $orderTrackingId !== '' ? $orderTrackingId : (string) $payment->pesapal_order_tracking_id;
            $statusData = $pesapalService->getTransactionStatus($trackingLookup);

            if ($pesapalService->isCompletedStatus($statusData)) {
                $paidAmount = (float) ($statusData['amount'] ?? $payment->amount);
                if (abs($paidAmount - (float) $payment->amount) < 0.01) {
                    $enrollment = $this->completePesapalPayment($payment, $statusData);
                    return response()->json([
                        'status' => 'completed',
                        'redirect_url' => $enrollment
                            ? route('enrollment.success', $enrollment->id)
                            : route('student.campus.invoices.index'),
                    ]);
                }
            } elseif ($pesapalService->isFailedStatus($statusData)) {
                $payment->update([
                    'status' => Payment::STATUS_FAILED,
                    'verification_status' => 'rejected',
                ]);
                return response()->json([
                    'status' => 'failed',
                    'message' => (string) ($statusData['payment_status_description'] ?? 'Payment was not completed.'),
                ]);
            }
        } catch (\Throwable) {
        }

        return response()->json(['status' => 'pending']);
    }

    /**
     * Handle asynchronous Pesapal API 3.0 IPN webhook notifications.
     */
    public function pesapalIpn(Request $request)
    {
        $orderTrackingId = trim((string) ($request->input('OrderTrackingId')
            ?? $request->query('OrderTrackingId')
            ?? $request->input('orderTrackingId')
            ?? $request->query('orderTrackingId', '')));
        $merchantReference = trim((string) ($request->input('OrderMerchantReference')
            ?? $request->query('OrderMerchantReference')
            ?? $request->input('orderMerchantReference')
            ?? $request->query('orderMerchantReference', '')));
        $notificationType = (string) ($request->input('OrderNotificationType') ?? $request->query('OrderNotificationType', 'IPNCHANGE'));

        if ($orderTrackingId === '' && $merchantReference === '') {
            return response()->json([
                'orderNotificationType'  => $notificationType,
                'orderTrackingId'        => $orderTrackingId,
                'orderMerchantReference' => $merchantReference,
                'status'                 => 500,
            ], 400);
        }

        $payment = $this->findPesapalPayment($orderTrackingId, $merchantReference);

        if (! $payment) {
            return response()->json([
                'orderNotificationType'  => $notificationType,
                'orderTrackingId'        => $orderTrackingId,
                'orderMerchantReference' => $merchantReference,
                'status'                 => 500,
            ], 404);
        }

        try {
            $pesapalService = app(\App\Services\PesapalService::class);
            $trackingLookup = $orderTrackingId !== '' ? $orderTrackingId : (string) $payment->pesapal_order_tracking_id;
            $statusData = $pesapalService->getTransactionStatus($trackingLookup);

            if ($pesapalService->isCompletedStatus($statusData)) {
                $paidAmount = (float) ($statusData['amount'] ?? $payment->amount);
                if (abs($paidAmount - (float) $payment->amount) < 0.01) {
                    $this->completePesapalPayment($payment, $statusData);
                }
            } elseif ($pesapalService->isFailedStatus($statusData) && $payment->status !== Payment::STATUS_COMPLETED) {
                $payment->update([
                    'status' => Payment::STATUS_FAILED,
                    'verification_status' => 'rejected',
                ]);
            }

            return response()->json([
                'orderNotificationType'  => $notificationType,
                'orderTrackingId'        => $trackingLookup,
                'orderMerchantReference' => $merchantReference ?: ($payment->pesapal_merchant_reference ?? ''),
                'status'                 => 200,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Pesapal IPN error: ' . $e->getMessage());

            return response()->json([
                'orderNotificationType'  => $notificationType,
                'orderTrackingId'        => $orderTrackingId,
                'orderMerchantReference' => $merchantReference,
                'status'                 => 500,
            ], 500);
        }
    }

    /**
     * Finalize a Pesapal payment and approve associated course, invoice, or bundle enrollments idempotently.
     *
     * @param array<string, mixed> $statusData
     */
    private function completePesapalPayment(Payment $payment, array $statusData): ?Enrollment
    {
        $newlyCompleted = false;

        $enrollment = DB::transaction(function () use ($payment, $statusData, &$newlyCompleted) {
            $lockedPayment = Payment::withoutGlobalScope('branch')->with('enrollment')->lockForUpdate()->findOrFail($payment->id);
            $alreadyCompleted = $lockedPayment->status === Payment::STATUS_COMPLETED;

            $confirmationCode = (string) ($statusData['confirmation_code'] ?? $lockedPayment->pesapal_confirmation_code ?? '');
            $paymentAccount = (string) ($statusData['payment_account'] ?? $lockedPayment->pesapal_payment_account ?? '');

            $lockedPayment->update([
                'pesapal_confirmation_code' => $confirmationCode !== '' ? $confirmationCode : null,
                'pesapal_payment_account'   => $paymentAccount !== '' ? $paymentAccount : null,
                'transaction_id'            => $confirmationCode !== '' ? $confirmationCode : ($lockedPayment->transaction_id ?: $lockedPayment->pesapal_order_tracking_id),
                'payment_date'              => $lockedPayment->payment_date ?? now()->toDateString(),
                'verification_status'       => 'verified',
                'status'                    => Payment::STATUS_COMPLETED,
            ]);

            $invoice = $lockedPayment->invoice_id
                ? \App\Models\Invoice::withoutGlobalScope('branch')->with('enrollment')->find($lockedPayment->invoice_id)
                : null;
            $enrollment = $lockedPayment->enrollment ?? $invoice?->enrollment;

            if ($enrollment && ! $lockedPayment->enrollment_id) {
                $lockedPayment->forceFill(['enrollment_id' => $enrollment->id])->saveQuietly();
            }

            if (! $alreadyCompleted) {
                $newlyCompleted = true;
            }

            app(\App\Services\SrsWorkflowService::class)->finalizeCompletedPayment($lockedPayment->fresh());

            return $enrollment?->fresh();
        });

        if ($newlyCompleted && $enrollment) {
            $this->sendEnrollmentNotifications($enrollment);
        }

        return $enrollment;
    }

    /**
     * Find a Pesapal payment by tracking ID or merchant reference bypassing branch scope.
     */
    private function findPesapalPayment(string $orderTrackingId, string $merchantReference = ''): ?Payment
    {
        return Payment::withoutGlobalScope('branch')
            ->where(function ($q) use ($orderTrackingId, $merchantReference): void {
                if ($orderTrackingId !== '') {
                    $q->where('pesapal_order_tracking_id', $orderTrackingId);
                    if ($merchantReference !== '') {
                        $q->orWhere('pesapal_merchant_reference', $merchantReference);
                    }
                } elseif ($merchantReference !== '') {
                    $q->where('pesapal_merchant_reference', $merchantReference);
                }
            })
            ->first();
    }

    /**
     * Restore student auth session if needed and redirect cleanly after Pesapal completion.
     */
    private function redirectAfterPesapalCompletion(Payment $payment, ?Enrollment $enrollment)
    {
        $userId = $payment->user_id ?? $enrollment?->user_id;
        if ($userId) {
            $user = User::find($userId);
            if ($user && (! Auth::check() || (int) Auth::id() !== (int) $userId)) {
                Auth::login($user, true);
            }
        }

        ToastMagic::success('Payment successful! Your payment is completed and enrollment is active.');

        if ($enrollment) {
            return redirect()->route('enrollment.success', $enrollment->id);
        }

        return redirect()->route('student.campus.invoices.index');
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
     * Validate a coupon code for checkout (Web).
     */
    public function validateCoupon(Request $request)
    {
        try {
            $code = $request->input('code');
            $courseId = $request->input('course_id');
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please log in to apply a coupon.',
                ], 401);
            }

            $course = Course::findOrFail($courseId);
            $price = $this->calculatePrice($course);

            $coupon = $this->couponService->validate($code, $course, $user);
            $discount = $this->couponService->calculateDiscount($coupon, $price);
            $finalPrice = max(0, $price - $discount);

            return response()->json([
                'success' => true,
                'coupon' => [
                    'id' => $coupon->id,
                    'code' => $coupon->code,
                    'type' => $coupon->type,
                    'value' => (float) $coupon->value,
                ],
                'discount_amount' => $discount,
                'discount_amount_formatted' => currency_format($discount),
                'final_price' => $finalPrice,
                'final_price_formatted' => $finalPrice > 0 ? currency_format($finalPrice) : __('frontend.free'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Invalid coupon code.',
            ], 400);
        }
    }
}
