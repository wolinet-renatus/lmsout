@extends('layouts.app')

@push('styles')
<style>
    .lmsout-checkout-page {
        background: #f8fafc;
        padding: 36px 0 72px;
        min-height: 78vh;
        font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    .lmsout-co-topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 24px;
    }

    .lmsout-co-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 13.5px;
        font-weight: 600;
        color: #475569;
        text-decoration: none;
        transition: color 0.15s ease;
    }
    .lmsout-co-back:hover {
        color: var(--theme, #013ba6);
    }

    .lmsout-co-secure-pill {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 6px 14px;
        border-radius: 999px;
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #065f46;
        font-size: 12.5px;
        font-weight: 600;
    }

    .lmsout-co-heading {
        margin-bottom: 24px;
    }
    .lmsout-co-heading h1 {
        font-size: clamp(22px, 2.5vw, 28px);
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 6px;
        letter-spacing: -0.02em;
    }
    .lmsout-co-heading p {
        font-size: 14.5px;
        color: #64748b;
        margin: 0;
    }

    .lmsout-co-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03);
    }

    .lmsout-co-card-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 18px;
        padding-bottom: 14px;
        border-bottom: 1px solid #f1f5f9;
    }
    .lmsout-co-card-title h2 {
        font-size: 16.5px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .lmsout-co-step-num {
        width: 26px;
        height: 26px;
        border-radius: 8px;
        background: rgba(var(--theme-rgb, 1, 59, 166), 0.1);
        color: var(--theme, #013ba6);
        font-size: 13px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .lmsout-co-course-row {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .lmsout-co-course-thumb {
        width: 96px;
        height: 68px;
        border-radius: 12px;
        object-fit: cover;
        border: 1px solid #e2e8f0;
        flex-shrink: 0;
    }
    .lmsout-co-course-meta h3 {
        font-size: 16px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 6px;
        line-height: 1.35;
    }
    .lmsout-co-course-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        font-size: 12.5px;
        color: #64748b;
    }
    .lmsout-co-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 10px;
        border-radius: 999px;
        background: #f1f5f9;
        color: #334155;
        font-size: 12px;
        font-weight: 600;
    }

    .lmsout-co-gateway-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }

    .lmsout-co-gateway-option {
        position: relative;
        margin: 0;
        cursor: pointer;
        display: block;
    }
    .lmsout-co-gateway-option input[type="radio"] {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    .lmsout-co-gateway-box {
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px 16px;
        background: #ffffff;
        transition: all 0.18s ease;
        height: 100%;
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }
    .lmsout-co-gateway-option:hover .lmsout-co-gateway-box {
        border-color: #94a3b8;
        background: #f8fafc;
    }
    .lmsout-co-gateway-option input:checked + .lmsout-co-gateway-box {
        border-color: var(--theme, #013ba6);
        background: rgba(var(--theme-rgb, 1, 59, 166), 0.04);
        box-shadow: 0 0 0 3px rgba(var(--theme-rgb, 1, 59, 166), 0.12);
    }

    .lmsout-co-radio-dot {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        border: 2px solid #cbd5e1;
        margin-top: 2px;
        flex-shrink: 0;
        position: relative;
        transition: all 0.15s ease;
    }
    .lmsout-co-gateway-option input:checked + .lmsout-co-gateway-box .lmsout-co-radio-dot {
        border-color: var(--theme, #013ba6);
    }
    .lmsout-co-gateway-option input:checked + .lmsout-co-gateway-box .lmsout-co-radio-dot::after {
        content: "";
        position: absolute;
        inset: 3px;
        border-radius: 50%;
        background: var(--theme, #013ba6);
    }

    .lmsout-co-gateway-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        font-size: 14.5px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 4px;
    }
    .lmsout-co-gateway-tag {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 2px 8px;
        border-radius: 999px;
        background: rgba(var(--theme-rgb, 1, 59, 166), 0.1);
        color: var(--theme, #013ba6);
    }
    .lmsout-co-gateway-tag--offline {
        background: #fef3c7;
        color: #92400e;
    }
    .lmsout-co-gateway-desc {
        font-size: 12.5px;
        color: #64748b;
        line-height: 1.45;
        margin: 0;
    }

    /* Pesapal Embedded Checkout Panel */
    .lmsout-pesapal-panel {
        border: 1px solid #dbeafe;
        border-radius: 14px;
        background: #f8fbff;
        overflow: hidden;
        margin-top: 8px;
    }
    .lmsout-pesapal-panel__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        padding: 12px 16px;
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
    }
    .lmsout-pesapal-panel__brand {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 13.5px;
        font-weight: 700;
        color: #0f172a;
    }
    .lmsout-pesapal-panel__rails {
        font-size: 12px;
        font-weight: 500;
        color: #64748b;
    }
    .lmsout-pesapal-panel__actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .lmsout-pesapal-btn-sm {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 11px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #334155;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .lmsout-pesapal-btn-sm:hover {
        border-color: var(--theme, #013ba6);
        color: var(--theme, #013ba6);
    }

    .lmsout-pesapal-loading,
    .lmsout-pesapal-error {
        padding: 48px 24px;
        text-align: center;
    }
    .lmsout-pesapal-spinner {
        width: 40px;
        height: 40px;
        border: 3px solid #dbeafe;
        border-top-color: var(--theme, #013ba6);
        border-radius: 50%;
        animation: lmsoutSpin 0.8s linear infinite;
        margin: 0 auto 14px;
    }
    @keyframes lmsoutSpin {
        to { transform: rotate(360deg); }
    }
    .lmsout-pesapal-iframe {
        width: 100%;
        min-height: 620px;
        height: 620px;
        border: 0;
        display: block;
        background: #ffffff;
    }

    /* Offline panel */
    .lmsout-offline-panel {
        border: 1px solid #fde68a;
        background: #fffbeb;
        border-radius: 14px;
        padding: 20px;
        margin-top: 8px;
    }
    .lmsout-offline-panel .form-control {
        border-radius: 10px;
        border: 1px solid #cbd5e1;
        padding: 10px 14px;
        font-size: 14px;
        background: #ffffff;
    }
    .lmsout-offline-panel .form-control:focus {
        border-color: var(--theme, #013ba6);
        box-shadow: 0 0 0 3px rgba(var(--theme-rgb, 1, 59, 166), 0.12);
    }

    /* Sticky Summary Sidebar */
    .lmsout-co-summary {
        position: sticky;
        top: 24px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
    }
    .lmsout-co-summary h3 {
        font-size: 17px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f1f5f9;
    }
    .lmsout-co-line {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        font-size: 14px;
        color: #475569;
        margin-bottom: 10px;
    }
    .lmsout-co-line strong {
        color: #0f172a;
        text-align: right;
    }
    .lmsout-co-total {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 14px;
        margin-top: 14px;
        border-top: 1px solid #e2e8f0;
        font-size: 16px;
        font-weight: 700;
        color: #0f172a;
    }
    .lmsout-co-total__amount {
        font-size: 20px;
        color: var(--theme, #013ba6);
    }

    .lmsout-co-coupon {
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px dashed #e2e8f0;
    }
    .lmsout-co-coupon-row {
        display: flex;
        gap: 8px;
    }
    .lmsout-co-coupon-row input {
        flex: 1;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        padding: 8px 12px;
        font-size: 13.5px;
    }
    .lmsout-co-coupon-row button {
        border: 0;
        border-radius: 10px;
        padding: 8px 16px;
        background: #0f172a;
        color: #ffffff;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
    }
    .lmsout-co-coupon-row button:hover {
        background: var(--theme, #013ba6);
    }

    .lmsout-co-submit-btn {
        width: 100%;
        margin-top: 18px;
        padding: 13px 20px;
        border: 0;
        border-radius: 12px;
        background: var(--theme, #013ba6);
        color: #ffffff;
        font-size: 15px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: pointer;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .lmsout-co-submit-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 20px rgba(var(--theme-rgb, 1, 59, 166), 0.22);
    }
    .lmsout-co-submit-btn:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none;
    }

    .lmsout-co-auto-hint {
        margin-top: 16px;
        padding: 12px 14px;
        border-radius: 12px;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1e3a8a;
        font-size: 12.5px;
        line-height: 1.5;
    }

    .lmsout-co-trust {
        margin-top: 16px;
        padding-top: 14px;
        border-top: 1px solid #f1f5f9;
        display: grid;
        gap: 8px;
        font-size: 12.5px;
        color: #64748b;
    }
    .lmsout-co-trust-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .lmsout-co-trust-item i {
        color: #10b981;
    }

    @media (max-width: 767px) {
        .lmsout-checkout-page {
            padding: 20px 0 48px;
        }
        .lmsout-co-card,
        .lmsout-co-summary {
            padding: 18px;
        }
        .lmsout-co-course-row {
            flex-direction: column;
            align-items: flex-start;
        }
        .lmsout-pesapal-iframe {
            min-height: 660px;
            height: 660px;
        }
    }
</style>
@endpush

@section('content')
    @php
        $isBundle = isset($isBundle) && $isBundle;
        $itemTitle = $course->title ?? '';
        $itemImage = !empty($course->featured_image)
            ? asset('storage/' . $course->featured_image)
            : asset('assets/front/img/inner/courses-details/courses-details.jpg');

        $enabledOnlineGateways = $onlineGateways->where('is_enabled', true)->values();
        $hasOfflineGateway = $offlineGateway && $offlineGateway->is_enabled;
        $hasPesapalGateway = $enabledOnlineGateways->contains('identifier', 'pesapal');

        // Prioritize Pesapal as the default online gateway when active, else first enabled online gateway, else offline.
        $defaultGatewayIdentifier = old('payment_method');
        if (empty($defaultGatewayIdentifier)) {
            if ($hasPesapalGateway) {
                $defaultGatewayIdentifier = 'pesapal';
            } elseif ($enabledOnlineGateways->isNotEmpty()) {
                $defaultGatewayIdentifier = $enabledOnlineGateways->first()->identifier;
            } elseif ($hasOfflineGateway) {
                $defaultGatewayIdentifier = 'offline';
            }
        }

        $taxAmt = (tax_enabled() && $price > 0) ? calculate_tax($price) : 0;
        $totalWithTax = ($price > 0) ? ($price + $taxAmt) : 0;
        $formattedTotal = $price > 0 ? currency_format($totalWithTax) : __('frontend.free');
        $backRoute = !empty($campusInvoice)
            ? route('student.campus.invoices.index')
            : ($isBundle ? route('courses') : route('courses.show', $course->id));
    @endphp

    <section class="lmsout-checkout-page">
        <div class="container">
            <div class="lmsout-co-topbar">
                <a href="{{ $backRoute }}" class="lmsout-co-back">
                    <i class="fa-solid fa-arrow-left"></i>
                    {{ !empty($campusInvoice) ? 'Back to My Invoices' : ($isBundle ? 'Back to Courses' : 'Back to Course Details') }}
                </a>
                <span class="lmsout-co-secure-pill">
                    <i class="fa-solid fa-shield-check"></i>
                    Encrypted Seamless Checkout
                </span>
            </div>

            <div class="lmsout-co-heading">
                <h1>{{ __('frontend.complete_enrollment') }}</h1>
                <p>{{ __('frontend.review_details_proceed') }}</p>
            </div>

            <div class="row g-4 align-items-start">
                <div class="col-lg-8">
                    <form id="checkoutForm"
                          action="{{ $isBundle ? route('bundles.checkout.process', $course->id) : route('courses.checkout.process', $course->id) }}"
                          method="POST" enctype="multipart/form-data">
                        @csrf
                        @if ($isBundle)
                            <input type="hidden" name="type" value="bundle">
                        @endif
                        @if (!empty($campusInvoice))
                            <input type="hidden" name="invoice_id" value="{{ $campusInvoice->id }}">
                            @if (request('pay'))
                                <input type="hidden" name="pay" value="{{ request('pay') }}">
                            @endif
                        @endif
                        <input type="hidden" name="coupon_code" id="applied_coupon_code" value="">

                        {{-- Card 1: Course / Invoice Overview --}}
                        <div class="lmsout-co-card">
                            <div class="lmsout-co-card-title">
                                <h2><span class="lmsout-co-step-num">1</span> {{ __('frontend.course_summary') }}</h2>
                                @if (!empty($campusInvoice))
                                    <span class="lmsout-co-badge">
                                        <i class="fa-regular fa-file-invoice"></i>
                                        {{ $campusInvoice->invoice_number }}
                                    </span>
                                @endif
                            </div>

                            <div class="lmsout-co-course-row">
                                <img src="{{ $itemImage }}" alt="{{ $itemTitle }}" class="lmsout-co-course-thumb">
                                <div class="lmsout-co-course-meta flex-grow-1">
                                    <h3>{{ $itemTitle }}</h3>
                                    <div class="lmsout-co-course-badges">
                                        @if (isset($course->instructor))
                                            <span class="lmsout-co-badge">
                                                <i class="fa-regular fa-user"></i>
                                                {{ $course->instructor->name }}
                                            </span>
                                        @endif
                                        @if (!$isBundle && !empty($course->delivery_mode))
                                            <span class="lmsout-co-badge">
                                                <i class="fa-solid fa-layer-group"></i>
                                                {{ ucwords(str_replace('_', ' ', $course->delivery_mode)) }}
                                            </span>
                                        @endif
                                        @if (!empty($campusInvoice?->control_number))
                                            <span class="lmsout-co-badge">
                                                <i class="fa-solid fa-hashtag"></i>
                                                Control No: {{ $campusInvoice->control_number }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Card 2: Active Payment Gateways & Auto-Loaded Checkout --}}
                        @if ($price > 0)
                            <div class="lmsout-co-card payment-method">
                                <div class="lmsout-co-card-title">
                                    <h2><span class="lmsout-co-step-num">2</span> {{ __('frontend.select_payment_method') }}</h2>
                                    <span class="small text-muted">
                                        @if ($enabledOnlineGateways->isNotEmpty() && $hasOfflineGateway)
                                            Instant Online or Offline Bank/Control No.
                                        @elseif ($enabledOnlineGateways->isNotEmpty())
                                            Instant Online Payment
                                        @elseif ($hasOfflineGateway)
                                            Manual Bank / Receipt Verification
                                        @endif
                                    </span>
                                </div>

                                @if ($enabledOnlineGateways->isEmpty() && !$hasOfflineGateway)
                                    <div class="alert alert-warning mb-0">
                                        No active payment gateways are currently enabled. Please contact support.
                                    </div>
                                @else
                                    <div class="lmsout-co-gateway-list">
                                        @foreach ($enabledOnlineGateways as $gateway)
                                            @php $identifier = $gateway->identifier; @endphp
                                            <label class="lmsout-co-gateway-option" for="{{ $identifier }}">
                                                <input type="radio" name="payment_method" id="{{ $identifier }}"
                                                       value="{{ $identifier }}"
                                                       {{ $defaultGatewayIdentifier === $identifier ? 'checked' : '' }}>
                                                <div class="lmsout-co-gateway-box">
                                                    <span class="lmsout-co-radio-dot"></span>
                                                    <div class="flex-grow-1">
                                                        <div class="lmsout-co-gateway-title">
                                                            <span>{{ $gateway->name }}</span>
                                                            <span class="lmsout-co-gateway-tag">
                                                                {{ $identifier === 'pesapal' ? 'Express Iframe' : __('frontend.online_payment') }}
                                                            </span>
                                                        </div>
                                                        <p class="lmsout-co-gateway-desc">
                                                            {{ $gatewayDescriptions[$identifier] ?? __('frontend.pay_securely') }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </label>
                                        @endforeach

                                        @if ($hasOfflineGateway)
                                            <label class="lmsout-co-gateway-option" for="offline">
                                                <input type="radio" name="payment_method" id="offline"
                                                       value="offline"
                                                       {{ $defaultGatewayIdentifier === 'offline' ? 'checked' : '' }}>
                                                <div class="lmsout-co-gateway-box">
                                                    <span class="lmsout-co-radio-dot"></span>
                                                    <div class="flex-grow-1">
                                                        <div class="lmsout-co-gateway-title">
                                                            <span>{{ $offlineGateway->name ?? __('frontend.offline_payment') }}</span>
                                                            <span class="lmsout-co-gateway-tag lmsout-co-gateway-tag--offline">Manual / Bank</span>
                                                        </div>
                                                        <p class="lmsout-co-gateway-desc">{{ __('frontend.offline_payment_desc') }}</p>
                                                    </div>
                                                </div>
                                            </label>
                                        @endif
                                    </div>

                                    @error('payment_method')
                                        <div class="invalid-feedback d-block mb-3">{{ $message }}</div>
                                    @enderror

                                    {{-- Auto-loaded Embedded Pesapal Express Checkout --}}
                                    @if ($hasPesapalGateway)
                                        <div id="pesapalEmbeddedPanel" class="lmsout-pesapal-panel {{ $defaultGatewayIdentifier === 'pesapal' ? '' : 'd-none' }}">
                                            <div class="lmsout-pesapal-panel__header">
                                                <div class="lmsout-pesapal-panel__brand">
                                                    <i class="fa-solid fa-lock text-success"></i>
                                                    <span>Pesapal Express Checkout</span>
                                                    <span class="lmsout-pesapal-panel__rails d-none d-md-inline">M-Pesa · Mixx · Airtel · HaloPesa · Visa/Mastercard</span>
                                                </div>
                                                <div class="lmsout-pesapal-panel__actions">
                                                    <button type="button" id="pesapalReloadBtn" class="lmsout-pesapal-btn-sm">
                                                        <i class="fa-solid fa-rotate-right"></i> Reload
                                                    </button>
                                                    <a href="#" id="pesapalExternalLink" target="_blank" rel="noopener" class="lmsout-pesapal-btn-sm d-none">
                                                        <i class="fa-solid fa-arrow-up-right-from-square"></i> New Tab
                                                    </a>
                                                </div>
                                            </div>

                                            <div id="pesapalLoadingState" class="lmsout-pesapal-loading">
                                                <div class="lmsout-pesapal-spinner"></div>
                                                <h6 class="mb-1 fw-bold text-dark">Loading Pesapal Secure Payment...</h6>
                                                <p class="small text-muted mb-0">Connecting automatically so you can pay right on this page.</p>
                                            </div>

                                            <div id="pesapalErrorState" class="lmsout-pesapal-error d-none">
                                                <div class="text-danger mb-2"><i class="fa-solid fa-circle-exclamation fa-2x"></i></div>
                                                <h6 class="fw-bold text-dark mb-1">Could not initialize Pesapal checkout</h6>
                                                <p id="pesapalErrorText" class="small text-muted mb-3">Please check your connection and try again.</p>
                                                <button type="button" id="pesapalRetryBtn" class="btn btn-primary btn-sm px-3">
                                                    <i class="fa-solid fa-rotate-right me-1"></i> Retry Payment Form
                                                </button>
                                            </div>

                                            <div id="pesapalFrameWrap" class="d-none">
                                                <iframe id="pesapalCheckoutIframe"
                                                        title="Pesapal Secure Payment"
                                                        class="lmsout-pesapal-iframe"
                                                        allow="payment *; clipboard-write"
                                                        loading="eager"
                                                        referrerpolicy="no-referrer-when-downgrade"></iframe>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Offline Payment Upload Section --}}
                                    @if ($hasOfflineGateway)
                                        <div id="offlinePaymentSection"
                                             class="lmsout-offline-panel {{ $defaultGatewayIdentifier === 'offline' ? '' : 'd-none' }}">
                                            <h5 class="mb-3 fw-bold">{{ __('frontend.payment_details') }}</h5>
                                            @if (!empty($offlineInstructions))
                                                <div class="alert alert-info alert-preline mb-3" role="alert">
                                                    {{ $offlineInstructions }}
                                                </div>
                                            @endif
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label for="transaction_id" class="form-label fw-semibold">
                                                        {{ __('frontend.transaction_id') }} <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="text" name="transaction_id" id="transaction_id"
                                                           value="{{ old('transaction_id') }}"
                                                           placeholder="{{ __('frontend.transaction_id') }}"
                                                           class="form-control @error('transaction_id') is-invalid @enderror">
                                                    @error('transaction_id')
                                                        <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="receipt_file" class="form-label fw-semibold">
                                                        {{ __('frontend.upload_receipt') }} <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="file" name="receipt_file" id="receipt_file"
                                                           accept=".pdf,.jpg,.jpeg,.png"
                                                           class="form-control @error('receipt_file') is-invalid @enderror">
                                                    <small class="text-muted">{{ __('frontend.accepted_formats') }}</small>
                                                    @error('receipt_file')
                                                        <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        @endif

                        {{-- Fallback / Non-Pesapal Form Action Button --}}
                        <div class="form-actions {{ ($price > 0 && $defaultGatewayIdentifier === 'pesapal') ? 'd-none' : '' }}" id="mainSubmitWrap">
                            <button type="submit" class="lmsout-co-submit-btn" id="submitBtn">
                                @if ($price > 0)
                                    {{ __('frontend.proceed_to_payment') }}
                                @else
                                    {{ __('frontend.complete_enrollment') }}
                                @endif
                                <i class="fa-solid fa-arrow-up-right"></i>
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Sticky Summary Column --}}
                <div class="col-lg-4">
                    <div class="lmsout-co-summary">
                        <h3>{{ __('frontend.order_summary') }}</h3>

                        <div class="lmsout-co-line">
                            <span>{{ $isBundle ? __('frontend.bundle') : __('frontend.course') }}</span>
                            <strong>{{ \Illuminate\Support\Str::limit($itemTitle, 44) }}</strong>
                        </div>

                        @if (!empty($campusInvoice))
                            <div class="lmsout-co-line">
                                <span>Invoice Balance</span>
                                <strong>{{ currency_format((float) $campusInvoice->balance) }}</strong>
                            </div>
                        @endif

                        <div class="lmsout-co-line">
                            <span>Subtotal</span>
                            <span id="summary-original-price"><strong>{{ $price > 0 ? currency_format($price) : __('frontend.free') }}</strong></span>
                        </div>

                        <div class="lmsout-co-line d-none" id="summary-discount-row">
                            <span>{{ __('frontend.discount') }}</span>
                            <span id="summary-discount-val" class="text-success"><strong>-</strong></span>
                        </div>

                        @if (tax_enabled() && $price > 0)
                            <div class="lmsout-co-line" id="summary-tax-row">
                                <span>{{ tax_name() }} ({{ tax_rate() }}%)</span>
                                <span id="summary-tax-val" class="text-secondary"><strong>{{ currency_format($taxAmt) }}</strong></span>
                            </div>
                        @endif

                        <div class="lmsout-co-total">
                            <span>{{ __('frontend.total') }}</span>
                            <span id="summary-total-price" class="lmsout-co-total__amount">{{ $formattedTotal }}</span>
                        </div>

                        @if (!$isBundle && empty($campusInvoice) && $price > 0)
                            <div class="lmsout-co-coupon">
                                <label for="coupon_code_input" class="form-label small fw-semibold text-muted mb-1">Have a Promo Code?</label>
                                <div class="lmsout-co-coupon-row">
                                    <input type="text" id="coupon_code_input" placeholder="Enter coupon code">
                                    <button type="button" id="apply_coupon_btn">Apply</button>
                                </div>
                                <div id="coupon-message" class="small mt-2"></div>
                            </div>
                        @endif

                        <div id="pesapalSidebarHint" class="lmsout-co-auto-hint {{ ($price > 0 && $defaultGatewayIdentifier === 'pesapal') ? '' : 'd-none' }}">
                            <i class="fa-solid fa-circle-info me-1"></i>
                            Complete your payment directly in the embedded Pesapal window. You will be enrolled and redirected automatically once confirmed.
                        </div>

                        <button type="submit" form="checkoutForm" id="sidebarSubmitBtn"
                                class="lmsout-co-submit-btn {{ ($price > 0 && $defaultGatewayIdentifier === 'pesapal') ? 'd-none' : '' }}">
                            @if ($price > 0)
                                {{ __('frontend.proceed_to_payment') }}
                            @else
                                {{ __('frontend.complete_enrollment') }}
                            @endif
                            <i class="fa-solid fa-arrow-up-right"></i>
                        </button>

                        <div class="lmsout-co-trust">
                            <div class="lmsout-co-trust-item">
                                <i class="fa-solid fa-circle-check"></i>
                                <span>Instant enrollment confirmation on payment</span>
                            </div>
                            <div class="lmsout-co-trust-item">
                                <i class="fa-solid fa-shield-halved"></i>
                                <span>Verified gateway settlement &amp; receipt</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('scripts')
        <script>
            $(document).ready(function() {
                const basePrice = {{ (float) $price }};
                let effectivePrice = basePrice;
                let loadedPesapalKey = null;
                @php
                    $pesapalStatusBaseUrl = route('payment.pesapal.status');
                @endphp
                let pesapalLoading = false;
                let pesapalTrackingId = '';
                let pesapalPollTimer = null;
                const pesapalStatusBaseUrl = @json($pesapalStatusBaseUrl);

                function stopPesapalStatusPoll() {
                    if (pesapalPollTimer) {
                        clearInterval(pesapalPollTimer);
                        pesapalPollTimer = null;
                    }
                }

                function checkPesapalOrderStatus() {
                    if (!pesapalTrackingId) return;
                    $.getJSON(pesapalStatusBaseUrl, { order_tracking_id: pesapalTrackingId }, function(res) {
                        if (res && res.status === 'completed' && res.redirect_url) {
                            stopPesapalStatusPoll();
                            window.top.location.href = res.redirect_url;
                        } else if (res && res.status === 'failed') {
                            stopPesapalStatusPoll();
                            setPesapalUiState('error', res.message || 'Pesapal payment failed or was cancelled.');
                        }
                    });
                }

                function startPesapalStatusPoll(trackingId) {
                    stopPesapalStatusPoll();
                    pesapalTrackingId = trackingId || '';
                    if (!pesapalTrackingId) return;
                    pesapalPollTimer = setInterval(checkPesapalOrderStatus, 4000);
                }

                function currentOrderKey() {
                    return '{{ $course->id }}|{{ $campusInvoice->id ?? 0 }}|' + ($('#applied_coupon_code').val() || '');
                }

                function setPesapalUiState(state, message) {
                    const $loading = $('#pesapalLoadingState');
                    const $error = $('#pesapalErrorState');
                    const $frameWrap = $('#pesapalFrameWrap');

                    $loading.toggleClass('d-none', state !== 'loading');
                    $error.toggleClass('d-none', state !== 'error');
                    $frameWrap.toggleClass('d-none', state !== 'ready');

                    if (state === 'error' && message) {
                        $('#pesapalErrorText').text(message);
                    }
                }

                function initPesapalIframe(forceReload = false) {
                    if (!$('#pesapalEmbeddedPanel').length || effectivePrice <= 0) {
                        return;
                    }

                    const orderKey = currentOrderKey();
                    if (!forceReload && loadedPesapalKey === orderKey && $('#pesapalCheckoutIframe').attr('src')) {
                        setPesapalUiState('ready');
                        return;
                    }

                    if (pesapalLoading && !forceReload) {
                        return;
                    }

                    pesapalLoading = true;
                    $('#pesapalExternalLink').addClass('d-none').attr('href', '#');
                    setPesapalUiState('loading');

                    $.ajax({
                        url: $('#checkoutForm').attr('action'),
                        method: 'POST',
                        data: new FormData($('#checkoutForm')[0]),
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            pesapalLoading = false;
                            if (response.success && response.checkout_url) {
                                loadedPesapalKey = orderKey;
                                $('#pesapalCheckoutIframe').attr('src', response.checkout_url);
                                $('#pesapalExternalLink').attr('href', response.checkout_url).removeClass('d-none');
                                setPesapalUiState('ready');
                                startPesapalStatusPoll(response.order_tracking_id || '');
                            } else {
                                setPesapalUiState('error', response.message || 'Unable to generate Pesapal checkout URL.');
                            }
                        },
                        error: function(xhr) {
                            pesapalLoading = false;
                            let msg = 'Unable to connect to Pesapal right now. Please retry.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            }
                            setPesapalUiState('error', msg);
                        }
                    });
                }

                function updateGatewayVisibility() {
                    const selected = $('input[name="payment_method"]:checked').val();

                    if (effectivePrice <= 0) {
                        $('#pesapalEmbeddedPanel').addClass('d-none');
                        $('#offlinePaymentSection').addClass('d-none');
                        $('#transaction_id, #receipt_file').prop('required', false);
                        $('#pesapalSidebarHint').addClass('d-none');
                        $('#mainSubmitWrap, #sidebarSubmitBtn').removeClass('d-none');
                        return;
                    }

                    if (selected === 'pesapal') {
                        $('#offlinePaymentSection').addClass('d-none').removeClass('is-visible');
                        $('#transaction_id, #receipt_file').prop('required', false);
                        $('#pesapalEmbeddedPanel').removeClass('d-none');
                        $('#pesapalSidebarHint').removeClass('d-none');
                        $('#mainSubmitWrap, #sidebarSubmitBtn').addClass('d-none');
                        initPesapalIframe(false);
                    } else if (selected === 'offline') {
                        $('#pesapalEmbeddedPanel').addClass('d-none');
                        $('#pesapalSidebarHint').addClass('d-none');
                        $('#offlinePaymentSection').removeClass('d-none').addClass('is-visible');
                        $('#transaction_id, #receipt_file').prop('required', true);
                        $('#mainSubmitWrap, #sidebarSubmitBtn').removeClass('d-none');
                    } else {
                        $('#pesapalEmbeddedPanel').addClass('d-none');
                        $('#pesapalSidebarHint').addClass('d-none');
                        $('#offlinePaymentSection').addClass('d-none').removeClass('is-visible');
                        $('#transaction_id, #receipt_file').prop('required', false);
                        $('#mainSubmitWrap, #sidebarSubmitBtn').removeClass('d-none');
                    }
                }

                // Break cleanly out of the Pesapal iframe once callback redirects back to our own origin.
                $('#pesapalCheckoutIframe').on('load', function() {
                    checkPesapalOrderStatus();
                    try {
                        const frameHref = this.contentWindow && this.contentWindow.location ? this.contentWindow.location.href : '';
                        if (frameHref && frameHref.indexOf(window.location.origin) === 0) {
                            if (frameHref.indexOf('/enrollment/success') !== -1 || (frameHref.indexOf('/courses/') !== -1 && frameHref.indexOf('/checkout') === -1)) {
                                stopPesapalStatusPoll();
                                window.top.location.href = frameHref;
                            }
                        }
                    } catch (e) {
                        // Cross-origin Pesapal URL while customer interacts with checkout; expected.
                    }
                });

                $('#pesapalReloadBtn, #pesapalRetryBtn').on('click', function() {
                    initPesapalIframe(true);
                });

                $('input[name="payment_method"]').on('change', function() {
                    updateGatewayVisibility();
                });

                // Initialize default gateway (auto-loads Pesapal iframe if enabled & selected)
                updateGatewayVisibility();

                $('#apply_coupon_btn').on('click', function(e) {
                    e.preventDefault();
                    const code = ($('#coupon_code_input').val() || '').trim();
                    const courseId = '{{ $course->id }}';
                    const $message = $('#coupon-message');
                    const $btn = $(this);

                    if (!code) {
                        $message.html('<span class="text-danger">Please enter a coupon code.</span>');
                        return;
                    }

                    $btn.prop('disabled', true).text('Checking...');
                    $message.html('');

                    $.ajax({
                        url: '{{ route('coupons.validate') }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            code: code,
                            course_id: courseId
                        },
                        success: function(response) {
                            $btn.prop('disabled', false).text('Apply');
                            if (response.success) {
                                $message.html('<span class="text-success">Coupon applied successfully!</span>');
                                $('#applied_coupon_code').val(response.coupon.code);
                                $('#summary-discount-row').removeClass('d-none');
                                $('#summary-discount-val').html('<strong>-' + response.discount_amount_formatted + '</strong>');
                                $('#summary-total-price').text(response.final_price_formatted);
                                effectivePrice = parseFloat(response.final_price);
                                loadedPesapalKey = null;

                                if (effectivePrice <= 0) {
                                    $('.payment-method').slideUp();
                                    $('#submitBtn, #sidebarSubmitBtn').html('{{ __('frontend.complete_enrollment') }} <i class="fa-solid fa-arrow-up-right"></i>');
                                } else {
                                    $('.payment-method').slideDown();
                                    $('#submitBtn, #sidebarSubmitBtn').html('{{ __('frontend.proceed_to_payment') }} <i class="fa-solid fa-arrow-up-right"></i>');
                                }
                                updateGatewayVisibility();
                            } else {
                                $message.html('<span class="text-danger">' + (response.message || 'Invalid coupon code.') + '</span>');
                                resetCoupon();
                            }
                        },
                        error: function(xhr) {
                            $btn.prop('disabled', false).text('Apply');
                            const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Invalid coupon code.';
                            $message.html('<span class="text-danger">' + msg + '</span>');
                            resetCoupon();
                        }
                    });
                });

                function resetCoupon() {
                    $('#applied_coupon_code').val('');
                    $('#summary-discount-row').addClass('d-none');
                    $('#summary-discount-val').html('<strong>-</strong>');
                    $('#summary-total-price').text('{{ $formattedTotal }}');
                    effectivePrice = basePrice;
                    loadedPesapalKey = null;
                    $('.payment-method').slideDown();
                    updateGatewayVisibility();
                }

                $('#checkoutForm').on('submit', function(e) {
                    const paymentMethod = $('input[name="payment_method"]:checked').val();

                    if (effectivePrice > 0 && paymentMethod === 'pesapal') {
                        e.preventDefault();
                        initPesapalIframe(true);
                    } else if (effectivePrice > 0 && (paymentMethod === 'stripe' || paymentMethod === 'flutterwave' || paymentMethod === 'paypal')) {
                        e.preventDefault();
                        handleRedirectGatewayCheckout();
                    }
                });

                function handleRedirectGatewayCheckout() {
                    const $btns = $('#submitBtn, #sidebarSubmitBtn');
                    $btns.prop('disabled', true).html('{{ __('frontend.processing') }} <i class="fa fa-spinner fa-spin"></i>');

                    $.ajax({
                        url: $('#checkoutForm').attr('action'),
                        method: 'POST',
                        data: new FormData($('#checkoutForm')[0]),
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success && response.checkout_url) {
                                window.location.href = response.checkout_url;
                            } else {
                                $btns.prop('disabled', false).html('{{ __('frontend.proceed_to_payment') }} <i class="fa-solid fa-arrow-up-right"></i>');
                                ToastMagic.error(response.message || 'An error occurred. Please try again.');
                            }
                        },
                        error: function(xhr) {
                            $btns.prop('disabled', false).html('{{ __('frontend.proceed_to_payment') }} <i class="fa-solid fa-arrow-up-right"></i>');
                            const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'An error occurred. Please try again.';
                            if (typeof ToastMagic !== 'undefined') {
                                ToastMagic.error(msg);
                            } else {
                                alert(msg);
                            }
                        }
                    });
                }
            });
        </script>
    @endpush
@endsection
