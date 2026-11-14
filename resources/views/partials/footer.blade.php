@php
    $siteName = site_name();
    $logoUrl = branding_asset(['primary_logo_path', 'footer_logo_path', 'dark_logo_path', 'logos.primary', 'logos.footer'], 'assets/front/img/logo/brand-logo-v3.png');
@endphp

<footer class="edux-brand-footer">
    <!-- Logo-Inspired Asymmetric Arc & Yellow Sun Top Shape -->
    <div class="edux-brand-footer__top-shape" aria-hidden="true">
        <svg viewBox="0 0 1440 96" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <!-- Outer Sky-Blue Sweeping Ring Arc (#228ddd) matching the logo's C-ring -->
            <path d="M0 72C260 98 620 12 1040 28C1230 35 1365 58 1440 76V96H0V72Z" fill="#228ddd" fill-opacity="0.28"/>
            <path d="M180 64C480 18 920 8 1440 58V96H0V82C60 78 115 72 180 64Z" fill="#228ddd"/>
            <!-- Inner Deep Royal Blue Body (#013ba6 -> #04153b) -->
            <path d="M210 74C520 28 940 20 1440 68V96H0V88C70 85 135 81 210 74Z" fill="#04153b"/>
        </svg>
        <!-- Signature Yellow Sphere Accent (#f4ed2d) in the top-left open arc gap, just like the logo -->
        <div class="edux-brand-footer__sun-orb"></div>
        <div class="edux-brand-footer__arc-ring"></div>
    </div>

    <!-- Decorative Watermark of the Exact Brand Logo Geometry on the Right -->
    <svg class="edux-brand-footer__watermark" viewBox="0 0 320 320" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <!-- 3/4 Open Sky-Blue Ring (#228ddd) -->
        <path d="M160 28 A132 132 0 1 1 28 160" stroke="#228ddd" stroke-width="28" stroke-linecap="butt" stroke-opacity="0.12"/>
        <!-- Top-Left Yellow Sun Dot (#f4ed2d) -->
        <circle cx="72" cy="72" r="32" fill="#f4ed2d" fill-opacity="0.14"/>
        <!-- Central Royal Blue Geometric 'U' Emblem -->
        <path d="M112 106 H142 V182 C142 192 148 198 158 198 H192 L210 226 H152 C128 226 112 210 112 184 V106 Z" fill="#228ddd" fill-opacity="0.12"/>
        <rect x="176" y="106" width="30" height="72" fill="#228ddd" fill-opacity="0.12"/>
    </svg>

    <div class="container position-relative" style="z-index: 2;">
        <!-- Floating Newsletter Banner ("Stay in the loop") with Logo-inspired Corner Cut -->
        <div class="edux-brand-footer__newsletter">
            <div class="edux-brand-footer__newsletter-badge" aria-hidden="true">
                <span class="edux-brand-footer__newsletter-dot"></span>
            </div>
            <div class="edux-brand-footer__newsletter-text">
                <h3 class="edux-brand-footer__newsletter-title">Stay in the loop</h3>
                <p class="edux-brand-footer__newsletter-desc">
                    Get new courses, special offers, and learning tips straight to your inbox.
                </p>
            </div>
            <form action="{{ route('newsletter.subscribe') }}" method="POST" class="edux-brand-footer__newsletter-form">
                @csrf
                <input type="email" name="email" class="edux-brand-footer__newsletter-input" placeholder="Your email address" required>
                <button type="submit" class="edux-brand-footer__newsletter-btn">
                    Subscribe <i class="fa-solid fa-arrow-right ms-1"></i>
                </button>
            </form>
        </div>

        <!-- Main 4-Column Footer Grid -->
        <div class="row g-4 justify-content-between edux-brand-footer__main">
            <!-- Column 1: Brand Emblem + Our Identity -->
            <div class="col-xl-4 col-lg-4 col-md-6">
                <div class="edux-brand-footer__brand">
                    <a href="{{ route('home') }}" class="edux-brand-footer__logo-link">
                        <span class="edux-brand-footer__logo-emblem">
                            <img src="{{ $logoUrl }}" alt="{{ $siteName }} logo">
                        </span>
                        <span class="edux-brand-footer__logo-text">
                            The Open University of Tanzania<span class="edux-brand-footer__logo-dot">.</span>
                        </span>
                    </a>

                    <h4 class="edux-brand-footer__heading mt-2">Our Identity</h4>
                    <ul class="edux-brand-footer__identity-list">
                        <li><span class="edux-id-dot"></span>Open and online learning</li>
                        <li><span class="edux-id-dot"></span>Affordable and quality education for all</li>
                        <li><span class="edux-id-dot"></span>Open admission</li>
                        <li><span class="edux-id-dot"></span>Available in all regions</li>
                        <li><span class="edux-id-dot"></span>Blended Learning (One on One and online Learning)</li>
                        <li><span class="edux-id-dot"></span>Regular and On Demand Examination</li>
                    </ul>
                </div>
            </div>

            <!-- Column 2: Shortcuts -->
            <div class="col-xl-2 col-lg-2 col-md-6">
                <h4 class="edux-brand-footer__heading">Shortcuts</h4>
                <ul class="edux-brand-footer__links">
                    <li><a href="https://armis.out.ac.tz/login" target="_blank" rel="noopener">ARMIS <i class="fa-solid fa-arrow-up-right-from-square edux-ext-icon"></i></a></li>
                    <li><a href="https://student.out.ac.tz/" target="_blank" rel="noopener">Student Mail <i class="fa-solid fa-arrow-up-right-from-square edux-ext-icon"></i></a></li>
                    <li><a href="https://sso.out.ac.tz/" target="_blank" rel="noopener">Staff Mail <i class="fa-solid fa-arrow-up-right-from-square edux-ext-icon"></i></a></li>
                    <li><a href="https://dlia.out.ac.tz/" target="_blank" rel="noopener">Alumni Portal <i class="fa-solid fa-arrow-up-right-from-square edux-ext-icon"></i></a></li>
                    <li><a href="https://admission.out.ac.tz/" target="_blank" rel="noopener">Undergraduate Admission <i class="fa-solid fa-arrow-up-right-from-square edux-ext-icon"></i></a></li>
                    <li><a href="https://pgoa.out.ac.tz/" target="_blank" rel="noopener">Postgraduate Admission <i class="fa-solid fa-arrow-up-right-from-square edux-ext-icon"></i></a></li>
                </ul>
            </div>

            <!-- Column 3: Important Links -->
            <div class="col-xl-3 col-lg-3 col-md-6">
                <h4 class="edux-brand-footer__heading">Important Links</h4>
                <ul class="edux-brand-footer__links">
                    <li><a href="https://www.heslb.go.tz/home?lang=english" target="_blank" rel="noopener">Students Loans Board <i class="fa-solid fa-arrow-up-right-from-square edux-ext-icon"></i></a></li>
                    <li><a href="https://www.tcu.go.tz/" target="_blank" rel="noopener">Tanzania Commission for Universities <i class="fa-solid fa-arrow-up-right-from-square edux-ext-icon"></i></a></li>
                    <li><a href="https://www.moe.go.tz/" target="_blank" rel="noopener">Ministry of Education, Science and Technology <i class="fa-solid fa-arrow-up-right-from-square edux-ext-icon"></i></a></li>
                    <li><a href="https://taesa.go.tz/" target="_blank" rel="noopener">Public Service Recruitment Secretariat <i class="fa-solid fa-arrow-up-right-from-square edux-ext-icon"></i></a></li>
                    <li><a href="https://www.eastc.ac.tz/" target="_blank" rel="noopener">East Africa Statistical Training Center <i class="fa-solid fa-arrow-up-right-from-square edux-ext-icon"></i></a></li>
                </ul>
            </div>

            <!-- Column 4: CONTACT US -->
            <div class="col-xl-3 col-lg-3 col-md-6">
                <h4 class="edux-brand-footer__heading">CONTACT US</h4>
                <ul class="edux-brand-footer__contact">
                    <li>
                        <i class="fa-solid fa-building-columns"></i>
                        <strong class="text-white">The Open University of Tanzania</strong>
                    </li>
                    <li>
                        <i class="fa-solid fa-envelopes-bulk"></i>
                        <span>P.O. Box 23409</span>
                    </li>
                    <li>
                        <i class="fa-solid fa-location-dot"></i>
                        <span>Kinondoni, Tanzania</span>
                    </li>
                    <li>
                        <i class="fa-solid fa-phone"></i>
                        <a href="tel:+255222668992">+255 22 2668992</a>
                    </li>
                    <li>
                        <i class="fa-solid fa-phone"></i>
                        <a href="tel:+255222668756">+255 22 2668756</a>
                    </li>
                    <li>
                        <i class="fa-regular fa-envelope"></i>
                        <a href="mailto:vc@out.ac.tz">vc@out.ac.tz</a>
                    </li>
                </ul>

                <div class="edux-brand-footer__socials mt-3">
                    @if (!empty($footerSocialLinks))
                        @foreach ($footerSocialLinks as $social)
                            <a href="{{ $social['url'] }}" target="_blank" rel="noopener" aria-label="Social Link">
                                <i class="{{ $social['icon'] ?? 'fab fa-link' }}"></i>
                            </a>
                        @endforeach
                    @else
                        <a href="https://www.out.ac.tz" target="_blank" rel="noopener" aria-label="Website"><i class="fa-solid fa-globe"></i></a>
                        <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="#" aria-label="X"><i class="fa-brands fa-x-twitter"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                        <a href="#" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Bottom Copyright Bar with Logo Accent Line -->
        <div class="edux-brand-footer__bottom">
            <p class="edux-brand-footer__copy">
                &copy; {{ date('Y') }} The Open University of Tanzania ({{ $siteName }}). All rights reserved.
            </p>
            <div class="edux-brand-footer__bottom-links">
                <a href="{{ route('courses') }}">Courses</a>
                <a href="{{ route('verify-certificate') }}">Verify Certificate</a>
                <a href="{{ url('/terms-conditions') }}">Terms &amp; Privacy</a>
                <a href="{{ route('contact') }}">Contact</a>
            </div>
        </div>
    </div>
</footer>
