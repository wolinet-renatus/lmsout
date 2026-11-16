<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\InstructorWithdrawal;
use App\Models\Payment;
use App\Models\PaymentGatewaySetting;
use App\Models\User;
use App\Services\PesapalService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Full-stack verification of Pesapal API 3.0 integration across Settings, Checkout,
 * Callback, IPN, Sanctum API, Admin/Student Payment Views, and Instructor Withdrawals.
 *
 * @author Renatus Bernardo
 */
class PesapalIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private User $instructor;

    private User $student;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        URL::forceRootUrl('http://localhost');
        Cache::flush();

        $this->admin = User::create([
            'name'                => 'Pesapal Admin',
            'email'               => 'psp-admin-' . Str::random(6) . '@out.ac.tz',
            'password'            => Hash::make('Secret@123'),
            'role'                => 'admin',
            'is_approved'         => true,
            'password_changed_at' => now(),
        ]);

        $this->instructor = User::create([
            'name'                => 'Pesapal Instructor',
            'email'               => 'psp-inst-' . Str::random(6) . '@out.ac.tz',
            'password'            => Hash::make('Secret@123'),
            'role'                => 'instructor',
            'is_approved'         => true,
            'balance'             => 250000.00,
            'password_changed_at' => now(),
        ]);

        $this->student = User::create([
            'name'                => 'Pesapal Student',
            'email'               => 'psp-stud-' . Str::random(6) . '@out.ac.tz',
            'phone'               => '+255712345678',
            'password'            => Hash::make('Secret@123'),
            'role'                => 'student',
            'is_approved'         => true,
            'password_changed_at' => now(),
        ]);

        $this->course = Course::create([
            'title'         => 'Digital Innovation Short Course ' . Str::random(4),
            'course_type'   => 'course',
            'delivery_mode' => 'online',
            'description'   => 'Full-stack Pesapal test course',
            'instructor_id' => $this->instructor->id,
            'pricing_model' => 'paid',
            'regular_price' => 50000.00,
            'status'        => 'published',
            'visibility'    => 'public',
        ]);
    }

    public function test_tanzania_merchant_pesapal_gateway_is_seeded_and_configurable_in_admin_ui(): void
    {
        $gateway = PaymentGatewaySetting::where('identifier', 'pesapal')->first();
        $this->assertNotNull($gateway);
        $this->assertTrue((bool) $gateway->is_enabled);
        $this->assertSame('ngW+UEcnDhltUc5fxPfrCD987xMh3Lx8', $gateway->credentials['consumer_key'] ?? null);
        $this->assertSame('q27RChYs5UkypdcNYKzuUw460Dg=', $gateway->credentials['consumer_secret'] ?? null);

        $this->actingAs($this->admin)
            ->get(route('admin.settings.gateways.index'))
            ->assertOk()
            ->assertSee('Pesapal')
            ->assertSee('ngW+UEcnDhltUc5fxPfrCD987xMh3Lx8')
            ->assertSee(route('payment.pesapal.callback'))
            ->assertSee(route('payment.pesapal.ipn'));

        $this->actingAs($this->admin)
            ->put(route('admin.settings.gateways.update', 'pesapal'), [
                'is_enabled'  => '1',
                'credentials' => [
                    'consumer_key'    => 'ngW+UEcnDhltUc5fxPfrCD987xMh3Lx8',
                    'consumer_secret' => 'q27RChYs5UkypdcNYKzuUw460Dg=',
                    'ipn_id'          => 'ipn-uuid-1234',
                    'mode'            => 'live',
                ],
            ])
            ->assertRedirect();

        $gateway->refresh();
        $this->assertSame('live', $gateway->credentials['mode'] ?? null);
        $this->assertSame('ipn-uuid-1234', $gateway->credentials['ipn_id'] ?? null);
    }

    public function test_web_pesapal_checkout_and_callback_complete_enrollment_and_show_in_admin_and_student_cards(): void
    {
        // Reset ipn_id inside this rolled-back transaction so auto-registration is exercised explicitly.
        $setting = PaymentGatewaySetting::where('identifier', 'pesapal')->firstOrFail();
        $creds = is_array($setting->credentials) ? $setting->credentials : [];
        unset($creds['ipn_id']);
        $setting->update(['credentials' => $creds]);
        app(\App\Services\PaymentGatewayService::class)->refreshCache();

        Http::fake([
            '*/api/Auth/RequestToken' => Http::response([
                'token'      => 'psp-test-token-001',
                'expiryDate' => now()->addMinutes(5)->toIso8601String(),
                'status'     => '200',
                'message'    => 'Request processed successfully',
            ], 200),
            '*/api/URLSetup/GetIpnList' => Http::response([], 200),
            '*/api/URLSetup/RegisterIPN' => Http::response([
                'url'                   => route('payment.pesapal.ipn'),
                'ipn_id'                => 'auto-ipn-uuid-999',
                'ipn_status'            => 1,
                'status'                => '200',
            ], 200),
            '*/api/Transactions/SubmitOrderRequest' => Http::response([
                'order_tracking_id'  => 'trk-web-12345',
                'merchant_reference' => 'PSP-WEB-REF-1',
                'redirect_url'       => 'https://cybqa.pesapal.com/pesapaliframe/PesapalIframe3/Index?OrderTrackingId=trk-web-12345',
                'error'              => null,
                'status'             => '200',
            ], 200),
            '*/api/Transactions/GetTransactionStatus*' => Http::response([
                'payment_method'             => 'Mpesa',
                'amount'                     => 50000.00,
                'confirmation_code'          => 'MPESA98765XYZ',
                'payment_status_description' => 'COMPLETED',
                'status_code'                => 1,
                'payment_account'            => '255712345678',
                'merchant_reference'         => 'PSP-WEB-REF-1',
                'currency'                   => 'TZS',
                'status'                     => '200',
            ], 200),
        ]);

        // 1. Checkout page displays Pesapal radio option
        $this->actingAs($this->student)
            ->get(route('courses.checkout', $this->course->id))
            ->assertOk()
            ->assertSee('Pesapal');

        // 2. Initiate Pesapal checkout session
        $checkoutRes = $this->actingAs($this->student)
            ->postJson(route('courses.checkout.process', $this->course->id), [
                'payment_method' => 'pesapal',
            ]);

        $checkoutRes->assertOk()
            ->assertJson([
                'success'           => true,
                'order_tracking_id' => 'trk-web-12345',
            ]);

        $payment = Payment::where('pesapal_order_tracking_id', 'trk-web-12345')->first();
        $this->assertNotNull($payment);
        $this->assertSame(Payment::PAYMENT_METHOD_PESAPAL, $payment->payment_method);
        $this->assertSame(Payment::STATUS_PENDING, $payment->status);

        // Auto-registered IPN UUID persisted back to DB settings
        $this->assertSame(
            'auto-ipn-uuid-999',
            PaymentGatewaySetting::where('identifier', 'pesapal')->first()->credentials['ipn_id'] ?? null
        );

        // 3. Callback redirect verifies and approves enrollment
        $callbackRes = $this->actingAs($this->student)
            ->get(route('payment.pesapal.callback', [
                'OrderTrackingId'        => 'trk-web-12345',
                'OrderMerchantReference' => 'PSP-WEB-REF-1',
            ]));

        $callbackRes->assertRedirect(route('enrollment.success', $payment->enrollment_id));

        $payment->refresh();
        $this->assertSame(Payment::STATUS_COMPLETED, $payment->status);
        $this->assertSame('MPESA98765XYZ', $payment->pesapal_confirmation_code);
        $this->assertSame('255712345678', $payment->pesapal_payment_account);
        $this->assertSame('MPESA98765XYZ', $payment->transaction_id);
        $this->assertSame(Enrollment::STATUS_APPROVED, $payment->enrollment->status);

        // 4. Admin & Student tables/cards display Pesapal transaction metadata
        $this->actingAs($this->admin)
            ->get(route('admin.payments.index', ['payment_method' => 'pesapal']))
            ->assertOk()
            ->assertSee('Pesapal');

        $this->actingAs($this->admin)
            ->get(route('admin.payments.show', $payment->id))
            ->assertOk()
            ->assertSee('trk-web-12345')
            ->assertSee('MPESA98765XYZ')
            ->assertSee('255712345678');

        $this->actingAs($this->student)
            ->get(route('student.payments', ['method' => 'pesapal']))
            ->assertOk()
            ->assertSee('Pesapal')
            ->assertSee('MPESA98765XYZ');
    }

    public function test_pesapal_ipn_listener_and_sanctum_api_verify_work_without_csrf_blocks(): void
    {
        $enrollment = Enrollment::create([
            'user_id'   => $this->student->id,
            'course_id' => $this->course->id,
            'status'    => Enrollment::STATUS_PENDING,
        ]);

        $payment = Payment::create([
            'user_id'                    => $this->student->id,
            'enrollment_id'              => $enrollment->id,
            'payment_method'             => Payment::PAYMENT_METHOD_PESAPAL,
            'amount'                     => 50000.00,
            'status'                     => Payment::STATUS_PENDING,
            'pesapal_order_tracking_id'  => 'trk-ipn-67890',
            'pesapal_merchant_reference' => 'PSP-IPN-REF-2',
        ]);
        $enrollment->update(['payment_id' => $payment->id]);

        Http::fake([
            '*/api/Auth/RequestToken' => Http::response([
                'token'  => 'psp-test-token-002',
                'status' => '200',
            ], 200),
            '*/api/Transactions/GetTransactionStatus*' => Http::response([
                'payment_method'             => 'AirtelMoney',
                'amount'                     => 50000.00,
                'confirmation_code'          => 'AIRTEL112233',
                'payment_status_description' => 'COMPLETED',
                'status_code'                => 1,
                'payment_account'            => '255788001122',
                'merchant_reference'         => 'PSP-IPN-REF-2',
                'status'                     => '200',
            ], 200),
        ]);

        // Unauthenticated server-to-server GET IPN webhook completes payment cleanly
        $this->getJson(route('payment.pesapal.ipn', [
            'OrderTrackingId'        => 'trk-ipn-67890',
            'OrderMerchantReference' => 'PSP-IPN-REF-2',
            'OrderNotificationType'  => 'IPNCHANGE',
        ]))->assertOk()->assertJson([
            'orderTrackingId' => 'trk-ipn-67890',
            'status'          => 200,
        ]);

        $payment->refresh();
        $enrollment->refresh();
        $this->assertSame(Payment::STATUS_COMPLETED, $payment->status);
        $this->assertSame(Enrollment::STATUS_APPROVED, $enrollment->status);
        $this->assertSame('AIRTEL112233', $payment->pesapal_confirmation_code);

        // Idempotent Sanctum API verify & API payments list include Pesapal metadata
        Sanctum::actingAs($this->student);

        $this->postJson('/api/payments/pesapal/verify', [
            'order_tracking_id' => 'trk-ipn-67890',
            'enrollment_id'     => $enrollment->id,
        ])->assertOk()->assertJson(['success' => true]);

        $this->getJson('/api/user/payments')
            ->assertOk()
            ->assertJsonFragment([
                'payment_method' => 'pesapal',
                'transaction_id' => 'AIRTEL112233',
            ]);
    }

    public function test_instructor_withdrawal_supports_pesapal_payout_method(): void
    {
        $this->actingAs($this->instructor)
            ->get(route('instructor.withdrawals'))
            ->assertOk()
            ->assertSee('value="pesapal"', false);

        $this->actingAs($this->instructor)
            ->post(route('instructor.withdrawals.store'), [
                'method'         => 'pesapal',
                'amount'         => 10000.00,
                'method_details' => '255712345678 (Vodacom M-Pesa via Pesapal)',
            ])
            ->assertRedirect();

        $withdrawal = InstructorWithdrawal::where('instructor_id', $this->instructor->id)
            ->where('method', 'pesapal')
            ->first();
        $this->assertNotNull($withdrawal);
        $this->assertSame('10000.00', number_format((float) $withdrawal->amount, 2, '.', ''));
    }

    public function test_checkout_renders_native_pesapal_iframe_and_only_active_gateways(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('courses.checkout', $this->course->id));

        $response->assertOk()
            ->assertSee('id="pesapalCheckoutIframe"', false)
            ->assertSee('Pesapal Express Checkout')
            ->assertSee('lmsout-checkout-page', false)
            ->assertDontSee('breadcrumb-wrapper', false)
            ->assertDontSee('Offline Payments')
            ->assertDontSee('Pay securely with Stripe');
    }

    public function test_checkout_redirects_mismatched_course_invoice_url_instead_of_returning_404(): void
    {
        $f2fCourseA = Course::create([
            'title'                    => 'Campus Basic Skills ' . Str::random(4),
            'course_type'              => 'course',
            'delivery_mode'            => 'face_to_face',
            'allow_online_f2f_payment' => true,
            'description'              => 'F2F course A',
            'instructor_id'            => $this->instructor->id,
            'pricing_model'            => 'paid',
            'regular_price'            => 172000.00,
            'status'                   => 'published',
            'visibility'               => 'public',
        ]);

        $f2fCourseB = Course::create([
            'title'                    => 'Campus Repair Skills ' . Str::random(4),
            'course_type'              => 'course',
            'delivery_mode'            => 'face_to_face',
            'allow_online_f2f_payment' => true,
            'description'              => 'F2F course B',
            'instructor_id'            => $this->instructor->id,
            'pricing_model'            => 'paid',
            'regular_price'            => 390000.00,
            'status'                   => 'published',
            'visibility'               => 'public',
        ]);

        $branch = \App\Models\Branch::create([
            'name'      => 'Test Branch ' . Str::random(4),
            'code'      => 'TB-' . Str::random(4),
            'is_active' => true,
        ]);

        $country = \App\Models\Country::first() ?? \App\Models\Country::create([
            'iso'      => 'TZ',
            'name'     => 'TANZANIA',
            'nicename' => 'Tanzania',
            'iso3'     => 'TZA',
        ]);

        $applicant = \App\Models\Applicant::create([
            'user_id'         => $this->student->id,
            'branch_id'       => $branch->id,
            'country_id'      => $country->id,
            'full_name'       => $this->student->name,
            'phone'           => '0712345678',
            'email'           => $this->student->email,
            'education_level' => '4',
        ]);

        $invoice = \App\Models\Invoice::withoutGlobalScope('branch')->create([
            'invoice_number'   => 'INV-TEST-' . Str::random(6),
            'control_number'   => '9910' . random_int(10000000, 99999999),
            'applicant_id'     => $applicant->id,
            'user_id'          => $this->student->id,
            'course_id'        => $f2fCourseA->id,
            'branch_id'        => $branch->id,
            'total_amount'     => 172000.00,
            'discount_amount'  => 0.00,
            'net_amount'       => 172000.00,
            'paid_amount'      => 0.00,
            'balance'          => 172000.00,
            'payment_status'   => 'unpaid',
            'installment_plan' => 'full',
            'issued_at'        => now(),
        ]);

        // Visiting course B checkout with invoice belonging to course A redirects cleanly (not 404)
        $res = $this->actingAs($this->student)->get(route('courses.checkout', [
            'id'         => $f2fCourseB->id,
            'invoice_id' => $invoice->id,
        ]));

        $res->assertRedirect(route('courses.checkout', [
            'id'         => $f2fCourseA->id,
            'invoice_id' => $invoice->id,
        ]));

        // Resolved checkout page renders 200 OK with BOTH Pesapal iframe and Offline Payment
        $this->actingAs($this->student)
            ->get(route('courses.checkout', ['id' => $f2fCourseA->id, 'invoice_id' => $invoice->id]))
            ->assertOk()
            ->assertSee($invoice->invoice_number)
            ->assertSee('id="pesapalCheckoutIframe"', false)
            ->assertSee('Offline');
    }

    public function test_public_ipn_registers_directly_without_get_ipn_list_and_sanitizes_billing_address(): void
    {
        $setting = PaymentGatewaySetting::where('identifier', 'pesapal')->firstOrFail();
        $creds = is_array($setting->credentials) ? $setting->credentials : [];
        unset($creds['ipn_id']);
        $setting->update(['credentials' => $creds]);
        app(\App\Services\PaymentGatewayService::class)->refreshCache();

        Http::fake([
            '*/api/Auth/RequestToken' => Http::response([
                'token'      => 'psp-public-token',
                'expiryDate' => now()->addMinutes(5)->toIso8601String(),
                'status'     => '200',
            ], 200),
            '*/api/URLSetup/RegisterIPN' => Http::response([
                'url'        => 'https://lms.out.ac.tz/payment/pesapal/ipn',
                'ipn_id'     => 'public-ipn-uuid-777',
                'ipn_status' => 1,
                'status'     => '200',
            ], 200),
            '*/api/URLSetup/GetIpnList' => Http::response([], 500),
            '*/api/Transactions/SubmitOrderRequest' => Http::response([
                'order_tracking_id'  => 'trk-sanitized-777',
                'merchant_reference' => 'PSP-SANITIZED-777',
                'redirect_url'       => 'https://cybqa.pesapal.com/pesapaliframe/PesapalIframe3/Index?OrderTrackingId=trk-sanitized-777',
                'error'              => null,
                'status'             => '200',
            ], 200),
        ]);

        $order = app(PesapalService::class)->submitOrder([
            'merchant_reference' => 'PSP-SANITIZED-777',
            'currency'           => 'TZS',
            'amount'             => 50000,
            'description'        => 'Public IPN + Billing Address Sanitization',
            'callback_url'       => 'https://lms.out.ac.tz/payment/pesapal/callback',
            'ipn_url'            => 'https://lms.out.ac.tz/payment/pesapal/ipn',
            'customer_name'      => 'Amina / Ally',
            'customer_email'     => 'amina@out.ac.tz',
            'customer_phone'     => '',
        ]);

        $this->assertSame('trk-sanitized-777', $order['order_tracking_id']);

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'GetIpnList'));
        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), 'SubmitOrderRequest')) {
                return false;
            }

            $billing = $request->data()['billing_address'] ?? [];
            $allowedPattern = '/^[A-Za-z0-9\-_.:]+$/';

            return ! empty($billing['middle_name'])
                && preg_match($allowedPattern, $billing['middle_name']) === 1
                && preg_match($allowedPattern, $billing['line_1'] ?? '') === 1
                && preg_match($allowedPattern, $billing['city'] ?? '') === 1
                && ! empty($billing['phone_number']);
        });
    }

    public function test_pending_payments_auto_promote_to_completed_on_verification_manual_approve_or_pesapal_status_and_callback(): void
    {
        // 1. Newly created pending payments start with verification_status = 'pending' (not DB default 'verified')
        $enrollment1 = Enrollment::create([
            'user_id'   => $this->student->id,
            'course_id' => $this->course->id,
            'status'    => Enrollment::STATUS_PENDING,
        ]);

        $offlinePayment = Payment::create([
            'user_id'        => $this->student->id,
            'enrollment_id'  => $enrollment1->id,
            'payment_method' => Payment::PAYMENT_METHOD_OFFLINE,
            'amount'         => 50000.00,
            'status'         => Payment::STATUS_PENDING,
            'transaction_id' => 'OFF-AUTO-' . Str::random(6),
        ]);

        $this->assertSame('pending', $offlinePayment->fresh()->verification_status);
        $this->assertSame(Payment::STATUS_PENDING, $offlinePayment->fresh()->status);

        // 2. Marking verification_status = 'verified' automatically promotes status -> 'completed' & approves enrollment
        $offlinePayment->update(['verification_status' => 'verified']);
        $offlinePayment->refresh();
        $enrollment1->refresh();

        $this->assertSame(Payment::STATUS_COMPLETED, $offlinePayment->status);
        $this->assertSame('verified', $offlinePayment->verification_status);
        $this->assertNotNull($offlinePayment->payment_date);
        $this->assertSame(Enrollment::STATUS_APPROVED, $enrollment1->status);

        // 3. Manual admin approve transitions a pending (or out-of-sync verified/pending) payment cleanly
        $course2 = Course::create([
            'title'         => 'Manual Approve Verification Course ' . Str::random(4),
            'course_type'   => 'course',
            'delivery_mode' => 'online',
            'instructor_id' => $this->instructor->id,
            'pricing_model' => 'paid',
            'regular_price' => 30000.00,
            'status'        => 'published',
            'visibility'    => 'public',
        ]);
        $enrollment2 = Enrollment::create([
            'user_id'   => $this->student->id,
            'course_id' => $course2->id,
            'status'    => Enrollment::STATUS_PENDING,
        ]);
        $manualPayment = Payment::create([
            'user_id'        => $this->student->id,
            'enrollment_id'  => $enrollment2->id,
            'payment_method' => Payment::PAYMENT_METHOD_PESAPAL,
            'amount'         => 30000.00,
            'status'         => Payment::STATUS_PENDING,
            'pesapal_order_tracking_id' => 'trk-manual-' . Str::random(6),
        ]);
        // Simulate legacy stale DB state where verification_status='verified' while status='pending'
        \Illuminate\Support\Facades\DB::table('payments')
            ->where('id', $manualPayment->id)
            ->update(['verification_status' => 'verified', 'status' => Payment::STATUS_PENDING]);

        $this->actingAs($this->admin)
            ->post(route('admin.payments.approve', $manualPayment->id))
            ->assertRedirect();

        $manualPayment->refresh();
        $enrollment2->refresh();
        $this->assertSame(Payment::STATUS_COMPLETED, $manualPayment->status);
        $this->assertSame('verified', $manualPayment->verification_status);
        $this->assertSame(Enrollment::STATUS_APPROVED, $enrollment2->status);

        // 4. Pesapal status poll + callback complete invoice-linked payments even when enrollment_id is initially null
        $invoice = \App\Models\Invoice::withoutGlobalScope('branch')->create([
            'invoice_number'   => 'INV-PSP-' . Str::random(5),
            'user_id'          => $this->student->id,
            'course_id'        => $course2->id,
            'total_amount'     => 30000.00,
            'discount_amount'  => 0,
            'net_amount'       => 30000.00,
            'paid_amount'      => 0,
            'balance'          => 30000.00,
            'payment_status'   => 'unpaid',
            'installment_plan' => 'full',
            'issued_at'        => now()->toDateString(),
        ]);

        $pspPayment = Payment::create([
            'user_id'                    => $this->student->id,
            'invoice_id'                 => $invoice->id,
            'payment_method'             => Payment::PAYMENT_METHOD_PESAPAL,
            'amount'                     => 30000.00,
            'status'                     => Payment::STATUS_PENDING,
            'pesapal_order_tracking_id'  => 'trk-poll-auto-888',
            'pesapal_merchant_reference' => 'PSP-POLL-888',
        ]);

        Http::fake([
            '*/api/Auth/RequestToken' => Http::response([
                'token'      => 'psp-status-token-888',
                'expiryDate' => now()->addMinutes(5)->toIso8601String(),
                'status'     => '200',
            ], 200),
            '*/api/Transactions/GetTransactionStatus*' => Http::response([
                'payment_method'             => 'Mpesa',
                'amount'                     => 30000.00,
                'confirmation_code'          => 'MPESA888POLL',
                'payment_status_description' => 'Completed',
                'status_code'                => 1,
                'payment_account'            => '255712345678',
                'merchant_reference'         => 'PSP-POLL-888',
                'status'                     => '200',
            ], 200),
        ]);

        $this->actingAs($this->student)
            ->getJson(route('payment.pesapal.status', ['order_tracking_id' => 'trk-poll-auto-888']))
            ->assertOk()
            ->assertJsonPath('status', 'completed');

        $pspPayment->refresh();
        $invoice->refresh();
        $this->assertSame(Payment::STATUS_COMPLETED, $pspPayment->status);
        $this->assertSame('verified', $pspPayment->verification_status);
        $this->assertSame('MPESA888POLL', $pspPayment->pesapal_confirmation_code);
        $this->assertSame('paid', $invoice->payment_status);
        $this->assertEquals(0.0, (float) $invoice->balance);
    }
}
