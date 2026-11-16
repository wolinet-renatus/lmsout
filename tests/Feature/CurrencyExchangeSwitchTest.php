<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Verifies TSH (TZS) and USD currency switching, configurable exchange rate
 * (default 1 USD = 2500 TZS), admin course builder pricing currency toggle,
 * public & admin course listings, and checkout ledger base-currency conversion.
 *
 * @author Renatus Bernardo
 */
class CurrencyExchangeSwitchTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        URL::forceRootUrl('http://localhost');

        $this->admin = User::create([
            'name'                => 'Currency Test Admin',
            'email'               => 'curr-admin-' . Str::random(6) . '@out.ac.tz',
            'password'            => Hash::make('Secret@123'),
            'role'                => 'admin',
            'is_approved'         => true,
            'password_changed_at' => now(),
        ]);

        $this->student = User::create([
            'name'                => 'Currency Test Student',
            'email'               => 'curr-student-' . Str::random(6) . '@out.ac.tz',
            'phone'               => '+255712345678',
            'password'            => Hash::make('Secret@123'),
            'role'                => 'student',
            'is_approved'         => true,
            'password_changed_at' => now(),
        ]);
    }

    public function test_currency_conversion_helpers_and_aliases_work_at_default_rate(): void
    {
        $this->assertSame('TZS', normalize_currency_code('TSH'));
        $this->assertSame('TZS', normalize_currency_code('tzs'));
        $this->assertSame('USD', normalize_currency_code('$'));
        $this->assertSame(2500.0, usd_to_tzs_rate());

        $this->assertSame(347500.0, convert_currency(139, 'USD', 'TZS'));
        $this->assertSame(197500.0, convert_currency(79, 'USD', 'TSH'));
        $this->assertSame(68.8, convert_currency(172000, 'TZS', 'USD'));

        $this->assertSame('TZS 347,500.00', currency_format(139, 2, 'code', 'USD', 'TZS'));
        $this->assertSame('USD 68.80', currency_format(172000, 2, 'code', 'TZS', 'USD'));
        $this->assertSame('$68.80', currency_format(172000, 2, 'symbol', 'TZS', 'USD'));
    }

    public function test_platform_settings_updates_exchange_rate_and_immediately_affects_conversions(): void
    {
        $settings = settings('platform.general', []);

        $response = $this->actingAs($this->admin)->put(route('admin.settings.platform.update'), [
            'site_name'        => $settings['site_name'] ?? 'OUT LMS',
            'tagline'          => $settings['tagline'] ?? 'University Learning',
            'timezone'         => $settings['timezone'] ?? 'Africa/Dar_es_Salaam',
            'default_language' => $settings['default_language'] ?? 'en',
            'default_currency' => 'TZS',
            'usd_to_tzs_rate'  => 2600,
        ]);

        $response->assertRedirect();

        $this->assertSame(2600.0, usd_to_tzs_rate());
        $this->assertSame(260000.0, convert_currency(100, 'USD', 'TZS'));
        $this->assertSame(50.0, convert_currency(130000, 'TZS', 'USD'));
    }

    public function test_admin_course_6_edit_renders_and_updates_price_currency_and_prices(): void
    {
        $course6 = Course::find(6);
        if (!$course6) {
            $course6 = Course::create([
                'instructor_id'  => $this->admin->id,
                'course_type'    => 'course',
                'delivery_mode'  => 'online',
                'title'          => 'Course 6 Fallback',
                'visibility'     => 'public',
                'pricing_model'  => 'paid',
                'price_currency' => 'USD',
                'regular_price'  => 139.00,
                'sale_price'     => 79.00,
                'status'         => 'published',
            ]);
        }

        $editResponse = $this->actingAs($this->admin)->get(route('admin.courses.edit', $course6->id));
        $editResponse->assertOk();
        $editResponse->assertSee('name="price_currency"', false);
        $editResponse->assertSee('TSH (TZS)', false);
        $editResponse->assertSee('USD ($)', false);
        $editResponse->assertSee('data-exchange-rate="2500"', false);

        $updateResponse = $this->actingAs($this->admin)->put(route('admin.courses.update', $course6->id), [
            'title'          => $course6->title,
            'description'    => $course6->description ?? 'Updated description',
            'visibility'     => $course6->visibility ?? 'public',
            'pricing_model'  => 'paid',
            'price_currency' => 'TZS',
            'regular_price'  => 347500,
            'sale_price'     => 197500,
            'status'         => $course6->status ?? 'published',
        ]);

        $updateResponse->assertSessionDoesntHaveErrors();

        $course6->refresh();
        $this->assertSame('TZS', $course6->price_currency);
        $this->assertSame('347500.00', number_format((float) $course6->regular_price, 2, '.', ''));
        $this->assertSame('197500.00', number_format((float) $course6->sale_price, 2, '.', ''));
    }

    public function test_public_courses_and_admin_courses_switch_between_tzs_and_usd_with_session_persistence(): void
    {
        $usdCourse = Course::create([
            'instructor_id'  => $this->admin->id,
            'course_type'    => 'course',
            'delivery_mode'  => 'online',
            'title'          => 'Currency Switch USD Course ' . Str::random(5),
            'visibility'     => 'public',
            'pricing_model'  => 'paid',
            'price_currency' => 'USD',
            'regular_price'  => 139.00,
            'sale_price'     => 79.00,
            'status'         => 'published',
        ]);

        $tzsCourse = Course::create([
            'instructor_id'  => $this->admin->id,
            'course_type'    => 'course',
            'delivery_mode'  => 'online',
            'title'          => 'Currency Switch TZS Course ' . Str::random(5),
            'visibility'     => 'public',
            'pricing_model'  => 'paid',
            'price_currency' => 'TZS',
            'regular_price'  => 172000.00,
            'sale_price'     => null,
            'status'         => 'published',
        ]);

        // Public catalog in TZS mode (?currency=TZS)
        $tzsCatalog = $this->get(route('courses', [
            'currency' => 'TZS',
            'search'   => 'Currency Switch',
            'per_page' => 24,
        ]));
        $tzsCatalog->assertOk();
        $tzsCatalog->assertSee($usdCourse->title);
        $tzsCatalog->assertSee($tzsCourse->title);
        $tzsCatalog->assertSee('TZS 197,500.00');
        $tzsCatalog->assertSee('TZS 347,500.00');
        $tzsCatalog->assertSee('TZS 172,000.00');

        // Public catalog switched to USD (?currency=USD)
        $usdCatalog = $this->get(route('courses', [
            'currency' => 'USD',
            'search'   => 'Currency Switch',
            'per_page' => 24,
        ]));
        $usdCatalog->assertOk();
        $usdCatalog->assertSee('$79.00');
        $usdCatalog->assertSee('$139.00');
        $usdCatalog->assertSee('$68.80');

        // Session persists USD on subsequent request without ?currency=
        $persistedCatalog = $this->get(route('courses', [
            'search'   => 'Currency Switch',
            'per_page' => 24,
        ]));
        $persistedCatalog->assertOk();
        $persistedCatalog->assertSee('$79.00');
        $persistedCatalog->assertSee('$68.80');

        // Admin courses list respects USD session & switches back via ?currency=TSH
        $adminUsd = $this->actingAs($this->admin)->get(route('admin.courses.index', [
            'search' => 'Currency Switch',
        ]));
        $adminUsd->assertOk();
        $adminUsd->assertSee('USD 79.00');
        $adminUsd->assertSee('USD 68.80');

        $adminTzs = $this->actingAs($this->admin)->get(route('admin.courses.index', [
            'currency' => 'TSH',
            'search'   => 'Currency Switch',
        ]));
        $adminTzs->assertOk();
        $adminTzs->assertSee('TZS 197,500.00');
        $adminTzs->assertSee('TZS 172,000.00');
    }

    public function test_checkout_keeps_base_currency_amount_for_payment_while_displaying_active_currency(): void
    {
        Http::fake([
            '*/api/Auth/RequestToken' => Http::response([
                'token'      => 'psp-curr-token-001',
                'expiryDate' => now()->addMinutes(5)->toIso8601String(),
                'status'     => '200',
                'message'    => 'Request processed successfully',
            ], 200),
            '*/api/URLSetup/RegisterIPN' => Http::response([
                'url'        => route('payment.pesapal.ipn'),
                'ipn_id'     => 'curr-ipn-uuid-999',
                'ipn_status' => 1,
                'status'     => '200',
            ], 200),
            '*/api/Transactions/SubmitOrderRequest' => Http::response([
                'order_tracking_id'  => 'trk-curr-12345',
                'merchant_reference' => 'PSP-CURR-REF-1',
                'redirect_url'       => 'https://cybqa.pesapal.com/pesapaliframe/PesapalIframe3/Index?OrderTrackingId=trk-curr-12345',
                'error'              => null,
                'status'             => '200',
            ], 200),
        ]);

        $usdCourse = Course::create([
            'instructor_id'  => $this->admin->id,
            'course_type'    => 'course',
            'delivery_mode'  => 'online',
            'title'          => 'Checkout Currency USD Course ' . Str::random(5),
            'visibility'     => 'public',
            'pricing_model'  => 'paid',
            'price_currency' => 'USD',
            'regular_price'  => 139.00,
            'sale_price'     => 79.00,
            'status'         => 'published',
        ]);

        // View checkout in USD mode -> displays USD 79.00 while ledger remains TZS 197,500.00
        $checkoutGet = $this->actingAs($this->student)->get(route('courses.checkout', [
            'id'       => $usdCourse->id,
            'currency' => 'USD',
        ]));
        $checkoutGet->assertOk();
        $checkoutGet->assertSee('USD 79.00');

        $expectedBaseSubtotal = 197500.0;
        $expectedBaseTotal = tax_enabled()
            ? round($expectedBaseSubtotal + calculate_tax($expectedBaseSubtotal), 2)
            : $expectedBaseSubtotal;

        $checkoutPost = $this->actingAs($this->student)->postJson(route('courses.checkout.process', $usdCourse->id), [
            'payment_method' => 'pesapal',
        ]);

        $checkoutPost->assertOk();

        // Also confirm homepage renders 200 cleanly after Blade header edits
        $this->get(route('home'))->assertOk();

        $payment = Payment::where('pesapal_order_tracking_id', 'trk-curr-12345')->first();
        $this->assertNotNull($payment);
        $this->assertSame($this->student->id, (int) $payment->user_id);
        $this->assertSame($usdCourse->id, (int) $payment->enrollment->course_id);
        $this->assertSame('pesapal', $payment->payment_method);
        $this->assertSame($expectedBaseTotal, (float) $payment->amount);

        Http::assertSent(function ($request) use ($expectedBaseTotal) {
            if (!str_contains($request->url(), '/Transactions/SubmitOrderRequest')) {
                return false;
            }
            $data = $request->data();

            return ($data['currency'] ?? null) === 'TZS'
                && (float) ($data['amount'] ?? 0) === $expectedBaseTotal;
        });
    }
}
