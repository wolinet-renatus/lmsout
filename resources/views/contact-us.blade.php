@extends('layouts.app')

@section('content')
    <!-- Gradient Hero Header -->
    <section class="lmsout-page-hero">
        <div class="container">
            <span class="lmsout-page-hero__eyebrow">
                <i class="fa-regular fa-envelope"></i> {{ $heroSettings['eyebrow'] ?? __('frontend.need_any_help') }}
            </span>
            <h1 class="lmsout-page-hero__title">
                {{ $heroSettings['heading'] ?? __('frontend.get_in_touch') }}
            </h1>
            <p class="lmsout-page-hero__desc">
                {{ $heroSettings['description'] ?? 'Have questions about admissions, courses, examinations, or regional centers? Reach out to The Open University of Tanzania team.' }}
            </p>
        </div>
    </section>

    <section class="lmsout-page-wrap">
        <div class="container">
            <div class="row g-4">
                <!-- Left Column: Official Contact Information & Shortcuts -->
                <div class="col-lg-5">
                    <div class="lmsout-surface-card">
                        <div class="lmsout-inner-header__eyebrow">OFFICIAL HEADQUARTERS</div>
                        <h3 class="fw-bold mb-3" style="font-size: 20px; color: #0f172a;">
                            The Open University of Tanzania
                        </h3>

                        <div class="d-flex flex-column gap-3 mb-4">
                            <div class="d-flex align-items-start gap-3 p-3 rounded-3" style="background: #f8fafc; border: 1px solid #f1f5f9;">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0" style="width: 38px; height: 38px; background: rgba(1, 59, 166, 0.08); color: var(--theme);">
                                    <i class="fa-solid fa-location-dot"></i>
                                </span>
                                <div>
                                    <div class="fw-bold" style="font-size: 13px; color: #0f172a;">Postal &amp; Physical Address</div>
                                    <div class="text-muted small">P.O. Box 23409, Kinondoni, Tanzania</div>
                                </div>
                            </div>

                            <div class="d-flex align-items-start gap-3 p-3 rounded-3" style="background: #f8fafc; border: 1px solid #f1f5f9;">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0" style="width: 38px; height: 38px; background: rgba(1, 59, 166, 0.08); color: var(--theme);">
                                    <i class="fa-solid fa-phone"></i>
                                </span>
                                <div>
                                    <div class="fw-bold" style="font-size: 13px; color: #0f172a;">Telephone</div>
                                    <div class="small">
                                        <a href="tel:+255222668992" class="text-decoration-none text-secondary d-block">+255 22 2668992</a>
                                        <a href="tel:+255222668756" class="text-decoration-none text-secondary d-block">+255 22 2668756</a>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex align-items-start gap-3 p-3 rounded-3" style="background: #f8fafc; border: 1px solid #f1f5f9;">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0" style="width: 38px; height: 38px; background: rgba(1, 59, 166, 0.08); color: var(--theme);">
                                    <i class="fa-regular fa-envelope"></i>
                                </span>
                                <div>
                                    <div class="fw-bold" style="font-size: 13px; color: #0f172a;">Official Email</div>
                                    <a href="mailto:vc@out.ac.tz" class="small text-decoration-none" style="color: var(--theme); font-weight: 600;">vc@out.ac.tz</a>
                                </div>
                            </div>
                        </div>

                        <div class="border-top pt-3">
                            <div class="text-uppercase fw-bold mb-2" style="font-size: 11px; letter-spacing: 0.06em; color: #64748b;">
                                Quick University Portals
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="https://armis.out.ac.tz/login" target="_blank" rel="noopener" class="lmsout-btn-outline" style="height: 32px; font-size: 11.5px; padding: 0 12px;">ARMIS</a>
                                <a href="https://admission.out.ac.tz/" target="_blank" rel="noopener" class="lmsout-btn-outline" style="height: 32px; font-size: 11.5px; padding: 0 12px;">Undergraduate Admission</a>
                                <a href="https://pgoa.out.ac.tz/" target="_blank" rel="noopener" class="lmsout-btn-outline" style="height: 32px; font-size: 11.5px; padding: 0 12px;">Postgraduate Admission</a>
                                <a href="https://student.out.ac.tz/" target="_blank" rel="noopener" class="lmsout-btn-outline" style="height: 32px; font-size: 11.5px; padding: 0 12px;">Student Mail</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Contact Form Card -->
                <div class="col-lg-7">
                    <div class="lmsout-surface-card">
                        <div class="lmsout-inner-header__eyebrow">SEND AN INQUIRY</div>
                        <h3 class="fw-bold mb-3" style="font-size: 20px; color: #0f172a;">
                            {{ __('frontend.send_message') }}
                        </h3>

                        @if (session('success'))
                            <div class="alert alert-success rounded-3">{{ session('success') }}</div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger rounded-3">{{ session('error') }}</div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger rounded-3">
                                <ul class="mb-0 small">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('contact.submit') }}" method="POST">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="lmsout-form-label">{{ __('frontend.your_name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name" class="lmsout-form-input" placeholder="{{ __('frontend.your_name') }}" value="{{ old('name', optional(auth()->user())->name) }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="lmsout-form-label">{{ __('frontend.your_email') }} <span class="text-danger">*</span></label>
                                    <input type="email" name="email" id="email" class="lmsout-form-input" placeholder="{{ __('frontend.your_email') }}" value="{{ old('email', optional(auth()->user())->email) }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="lmsout-form-label">{{ __('frontend.phone_number') }}</label>
                                    <input type="text" name="phone" id="phone" class="lmsout-form-input" placeholder="+255 ..." value="{{ old('phone') }}">
                                </div>
                                <div class="col-md-6">
                                    <label for="subject" class="lmsout-form-label">{{ __('frontend.subject') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="subject" id="subject" class="lmsout-form-input" placeholder="{{ __('frontend.subject') }}" value="{{ old('subject') }}" required>
                                </div>
                                <div class="col-12">
                                    <label for="message" class="lmsout-form-label">{{ __('frontend.message_here') }} <span class="text-danger">*</span></label>
                                    <textarea name="message" id="message" class="lmsout-form-textarea" placeholder="{{ __('frontend.message_here') }}" rows="5" required>{{ old('message') }}</textarea>
                                </div>
                                <div class="col-12 pt-1">
                                    <button type="submit" class="lmsout-btn-primary">
                                        <span>{{ $formSettings['submit_label'] ?? __('frontend.send_message') }}</span>
                                        <i class="fa-solid fa-paper-plane"></i>
                                    </button>
                                </div>
                            </div>
                        </form>

                        @if (!empty($formSettings['description']))
                            <p class="mt-3 text-muted small mb-0">{{ $formSettings['description'] }}</p>
                        @endif
                    </div>
                </div>
            </div>

            @if (!empty($mapSettings['embed_url']))
                <div class="lmsout-surface-card p-2 mt-4 overflow-hidden">
                    <iframe src="{{ $mapSettings['embed_url'] }}" style="border:0; width: 100%; height: 340px; border-radius: 12px;" allowfullscreen loading="lazy"></iframe>
                </div>
            @endif
        </div>
    </section>
@endsection
