@php
    $themeColors = \Illuminate\Support\Facades\Schema::hasTable('frontend_settings')
        ? \App\Models\FrontendSetting::getValue('theme.colors', [])
        : [];
    $primaryColor = $themeColors['primary'] ?? branding_settings('primary_color', '#013ba6');
    $secondaryColor = $themeColors['secondary'] ?? branding_settings('secondary_color', '#228ddd');
    $accentColor = $themeColors['accent'] ?? '#f4ed2d';
@endphp
<style>
    :root {
        --body: #081c45;
        --header: #081c45;
        --text: #1e293b;
        --theme: {{ $primaryColor }};
        --theme-2: {{ $secondaryColor }};
        --theme-accent: {{ $accentColor }};
        --theme-yellow: {{ $accentColor }};
        --bg: #eef5fc;
        --bg-2: {{ $primaryColor }};
        --v2-primary: {{ $primaryColor }};
        --v2-secondary: {{ $secondaryColor }};
        --v2-bg: #eef5fc;
        --v2-header: #081c45;
    }

    /* ==========================================================================
       1. Disable Legacy Cursor & Floating Animations
       ========================================================================== */
    .mouseCursor,
    .cursor-outer,
    .cursor-inner,
    .header-1 .logo-shape,
    .logo-shape {
        display: none !important;
        visibility: hidden !important;
        pointer-events: none !important;
    }

    .float-bob-x,
    .float-bob-y,
    .bounce-x,
    .cir36,
    .breathing-animation,
    .animation__arryLeftRight {
        -webkit-animation: none !important;
        animation: none !important;
    }

    /* ==========================================================================
       2. Clean, Compact, Sticky Header Navigation Bar (.edux-navbar)
       ========================================================================== */
    header#header-sticky.edux-navbar,
    header#header-sticky.header-1.edux-navbar,
    header#header-sticky.header-1.edux-navbar.sticky {
        position: -webkit-sticky !important;
        position: sticky !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        width: 100% !important;
        z-index: 1030 !important;
        background: rgba(255, 255, 255, 0.97) !important;
        -webkit-backdrop-filter: blur(12px) !important;
        backdrop-filter: blur(12px) !important;
        border-bottom: 1px solid #e2e8f0 !important;
        box-shadow: 0 2px 14px rgba(15, 23, 42, 0.04) !important;
        padding: 0 !important;
        margin: 0 !important;
        height: 66px !important;
        min-height: 66px !important;
        display: flex !important;
        align-items: center !important;
        transition: box-shadow 0.2s ease, background-color 0.2s ease !important;
    }
    header#header-sticky.edux-navbar.sticky {
        box-shadow: 0 6px 24px rgba(1, 59, 166, 0.09) !important;
        border-bottom-color: rgba(34, 141, 221, 0.25) !important;
    }
    .edux-navbar .container,
    .edux-navbar .mega-menu-wrapper {
        width: 100%;
        height: 100%;
    }
    .edux-navbar .header-main.edux-navbar__inner {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: 16px !important;
        height: 66px !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .edux-navbar__logo-wrap {
        padding: 0 !important;
        margin: 0 !important;
        flex-shrink: 0;
    }
    a.edux-header-brand {
        display: inline-flex !important;
        align-items: center !important;
        gap: 10px !important;
        text-decoration: none !important;
    }
    .edux-header-brand__badge {
        width: 40px !important;
        height: 40px !important;
        border-radius: 10px !important;
        background: #ffffff !important;
        border: 1.5px solid rgba(34, 141, 221, 0.28) !important;
        box-shadow: 0 2px 8px rgba(1, 59, 166, 0.08) !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 3px !important;
        flex-shrink: 0 !important;
    }
    img.edux-header-brand__img {
        width: 32px !important;
        height: 32px !important;
        max-width: 32px !important;
        max-height: 32px !important;
        object-fit: contain !important;
        display: block !important;
    }
    .edux-header-brand__name {
        font-family: 'Plus Jakarta Sans', 'Inter', sans-serif !important;
        font-size: 17px !important;
        font-weight: 800 !important;
        color: #0f172a !important;
        letter-spacing: -0.02em !important;
        line-height: 1.1 !important;
        white-space: nowrap !important;
    }
    .edux-header-brand__dot {
        color: var(--theme-2, #228ddd) !important;
    }

    /* Center Menu Links */
    .edux-navbar .mean__menu-wrapper {
        flex: 1;
        display: flex;
        justify-content: center;
    }
    .edux-navbar .main-menu ul {
        display: flex !important;
        align-items: center !important;
        gap: 4px !important;
        margin: 0 !important;
        padding: 0 !important;
        list-style: none !important;
    }
    .edux-navbar .main-menu ul li {
        margin: 0 !important;
        padding: 0 !important;
        position: relative;
    }
    .edux-navbar .main-menu ul > li > a {
        display: inline-flex !important;
        align-items: center !important;
        gap: 5px !important;
        padding: 7px 13px !important;
        font-size: 13.5px !important;
        font-weight: 600 !important;
        color: #475569 !important;
        border-radius: 8px !important;
        text-decoration: none !important;
        line-height: 1.2 !important;
        transition: background-color 0.15s ease, color 0.15s ease !important;
    }
    .edux-navbar .main-menu ul > li > a:hover,
    .edux-navbar .main-menu ul > li.is-active > a,
    .edux-navbar .main-menu ul > li > a.is-active,
    .edux-navbar .main-menu ul > li > a.active {
        background: rgba(1, 59, 166, 0.08) !important;
        color: var(--theme) !important;
    }
    /* Dropdown Submenu in Header */
    .edux-navbar .main-menu ul li .submenu {
        position: absolute !important;
        top: calc(100% + 8px) !important;
        left: 0 !important;
        min-width: 210px !important;
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 12px !important;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.1) !important;
        padding: 6px !important;
        display: block !important;
        opacity: 0;
        visibility: hidden;
        transform: translateY(6px);
        transition: all 0.18s ease !important;
        z-index: 1050 !important;
    }
    .edux-navbar .main-menu ul li:hover > .submenu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    .edux-navbar .main-menu ul li .submenu li {
        display: block !important;
        width: 100% !important;
    }
    .edux-navbar .main-menu ul li .submenu li a {
        display: block !important;
        width: 100% !important;
        padding: 8px 12px !important;
        font-size: 13px !important;
        font-weight: 500 !important;
        color: #334155 !important;
        border-radius: 7px !important;
    }
    .edux-navbar .main-menu ul li .submenu li a:hover {
        background: #f8fafc !important;
        color: var(--theme) !important;
    }

    /* Right Navbar Actions */
    .edux-navbar__actions {
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
        flex-shrink: 0;
    }
    .edux-navbar__search-pill {
        height: 36px;
        padding: 0 10px 0 12px;
        border-radius: 999px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #64748b;
        font-size: 12.5px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        cursor: pointer;
        transition: border-color 0.15s ease, background-color 0.15s ease;
    }
    .edux-navbar__search-pill:hover {
        border-color: var(--theme-2, #228ddd);
        background: #ffffff;
        color: #0f172a;
    }
    .edux-navbar__search-pill i {
        font-size: 12px;
        color: #94a3b8;
    }
    .edux-navbar__search-pill kbd {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        padding: 1px 5px;
        font-size: 10px;
        font-weight: 600;
        color: #94a3b8;
        font-family: inherit;
    }
    .edux-navbar__lang {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 11.5px;
        font-weight: 600;
        color: #475569;
        padding: 5px 10px;
        border-radius: 999px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        white-space: nowrap;
    }
    .edux-navbar__lang i {
        color: var(--theme-2, #228ddd);
        font-size: 12px;
    }
    .edux-navbar__lang select {
        border: 0;
        background: transparent;
        font-size: 11.5px;
        font-weight: 600;
        color: #475569;
        outline: none;
        cursor: pointer;
    }
    .edux-navbar__cta-btn {
        height: 36px;
        padding: 0 15px;
        border-radius: 9px;
        background: var(--theme);
        color: #ffffff !important;
        font-size: 12.5px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none !important;
        white-space: nowrap;
        box-shadow: 0 2px 8px rgba(1, 59, 166, 0.2);
        transition: background-color 0.15s ease, transform 0.15s ease;
    }
    .edux-navbar__cta-btn:hover {
        background: var(--theme-2, #228ddd);
        color: #ffffff !important;
        transform: translateY(-1px);
    }
    .edux-navbar__Burger {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #0f172a;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    /* Other Logos (Mobile Drawer & Auth) */
    .offcanvas__logo a img,
    .login-logo img,
    .register-logo img {
        width: auto !important;
        height: 48px !important;
        max-height: 48px !important;
        object-fit: contain;
    }

    /* ==========================================================================
       3. Logo-Resembling Architectural Footer (.edux-brand-footer)
       ========================================================================== */
    .edux-brand-footer {
        position: relative;
        background: linear-gradient(180deg, #04153b 0%, #03102e 100%);
        color: rgba(255, 255, 255, 0.82);
        padding: 36px 0 28px;
        margin-top: 0;
        overflow: hidden;
        font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }
    .edux-brand-footer__top-shape {
        position: relative;
        width: 100%;
        height: 54px;
        margin-top: -36px;
        margin-bottom: 12px;
        pointer-events: none;
    }
    .edux-brand-footer__top-shape svg {
        width: 100%;
        height: 100%;
        display: block;
    }
    /* Signature Yellow Sphere (#f4ed2d) & Sky-Blue Ring (#228ddd) echoing the brand logo */
    .edux-brand-footer__sun-orb {
        position: absolute;
        top: 8px;
        left: 7%;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: var(--theme-yellow, #f4ed2d);
        box-shadow: 0 0 24px rgba(244, 237, 45, 0.65);
    }
    .edux-brand-footer__arc-ring {
        position: absolute;
        top: -2px;
        left: calc(7% - 10px);
        width: 42px;
        height: 42px;
        border-radius: 50%;
        border: 3.5px solid #228ddd;
        border-top-color: transparent;
        border-left-color: transparent;
        transform: rotate(-15deg);
        opacity: 0.85;
    }
    .edux-brand-footer__watermark {
        position: absolute;
        right: -50px;
        bottom: -40px;
        width: 340px;
        height: 340px;
        pointer-events: none;
        z-index: 1;
    }

    /* Floating Newsletter Banner inside Footer */
    .edux-brand-footer__newsletter {
        position: relative;
        background: linear-gradient(135deg, var(--theme, #013ba6) 0%, var(--theme-2, #228ddd) 100%);
        border: 1px solid rgba(255, 255, 255, 0.16);
        border-radius: 18px;
        padding: 26px 32px;
        margin-bottom: 44px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 20px;
        box-shadow: 0 18px 40px rgba(0, 0, 0, 0.28);
        overflow: hidden;
    }
    .edux-brand-footer__newsletter::after {
        content: '';
        position: absolute;
        top: -36px;
        right: -36px;
        width: 110px;
        height: 110px;
        border-radius: 50%;
        border: 14px solid rgba(244, 237, 45, 0.18);
        pointer-events: none;
    }
    .edux-brand-footer__newsletter-badge {
        display: none;
    }
    .edux-brand-footer__newsletter-title {
        font-size: 20px;
        font-weight: 800;
        color: #ffffff;
        margin: 0 0 4px;
    }
    .edux-brand-footer__newsletter-desc {
        font-size: 13px;
        color: rgba(255, 255, 255, 0.88);
        margin: 0;
    }
    .edux-brand-footer__newsletter-form {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 1;
        max-width: 430px;
    }
    .edux-brand-footer__newsletter-input {
        flex: 1;
        height: 42px;
        border-radius: 9px;
        border: 0;
        padding: 0 14px;
        font-size: 13px;
        color: #0f172a;
        background: #ffffff;
        outline: none;
        min-width: 0;
    }
    .edux-brand-footer__newsletter-btn {
        height: 42px;
        padding: 0 20px;
        border-radius: 9px;
        border: 0;
        background: var(--theme-yellow, #f4ed2d);
        color: #04153b;
        font-size: 13px;
        font-weight: 800;
        cursor: pointer;
        white-space: nowrap;
        transition: transform 0.15s ease, background-color 0.15s ease;
    }
    .edux-brand-footer__newsletter-btn:hover {
        background: #ffffff;
        transform: translateY(-1px);
    }

    /* Footer Columns */
    .edux-brand-footer__main {
        padding-bottom: 36px;
    }
    .edux-brand-footer__logo-link {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        margin-bottom: 14px;
    }
    .edux-brand-footer__logo-emblem {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        background: #ffffff;
        border: 2px solid #228ddd;
        box-shadow: 0 4px 14px rgba(34, 141, 221, 0.3);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 4px;
        flex-shrink: 0;
    }
    .edux-brand-footer__logo-emblem img {
        width: 36px !important;
        height: 36px !important;
        object-fit: contain !important;
    }
    .edux-brand-footer__logo-text {
        font-size: 16px;
        font-weight: 800;
        color: #ffffff;
        line-height: 1.25;
    }
    .edux-brand-footer__logo-dot {
        color: var(--theme-yellow, #f4ed2d);
    }
    .edux-brand-footer__heading {
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #ffffff;
        margin: 0 0 14px;
        position: relative;
        padding-bottom: 8px;
    }
    .edux-brand-footer__heading::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 28px;
        height: 2.5px;
        border-radius: 2px;
        background: linear-gradient(90deg, var(--theme-yellow, #f4ed2d) 0%, #228ddd 100%);
    }

    /* Our Identity List */
    .edux-brand-footer__identity-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 7px;
    }
    .edux-brand-footer__identity-list li {
        display: flex;
        align-items: flex-start;
        gap: 9px;
        font-size: 12.5px;
        color: rgba(255, 255, 255, 0.8);
        line-height: 1.45;
    }
    .edux-id-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: var(--theme-yellow, #f4ed2d);
        box-shadow: 0 0 0 2px rgba(34, 141, 221, 0.45);
        margin-top: 6px;
        flex-shrink: 0;
    }

    /* Shortcuts & Important Links */
    .edux-brand-footer__links {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 9px;
    }
    .edux-brand-footer__links li a {
        color: rgba(255, 255, 255, 0.78);
        font-size: 12.5px;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        line-height: 1.4;
        transition: color 0.15s ease, transform 0.15s ease;
    }
    .edux-brand-footer__links li a:hover {
        color: var(--theme-yellow, #f4ed2d);
        transform: translateX(3px);
    }
    .edux-ext-icon {
        font-size: 9.5px;
        opacity: 0.65;
    }

    /* Contact Us List */
    .edux-brand-footer__contact {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 9px;
    }
    .edux-brand-footer__contact li {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        font-size: 12.5px;
        color: rgba(255, 255, 255, 0.82);
        line-height: 1.45;
    }
    .edux-brand-footer__contact li i {
        color: #228ddd;
        font-size: 13px;
        margin-top: 3px;
        width: 15px;
        text-align: center;
        flex-shrink: 0;
    }
    .edux-brand-footer__contact li a {
        color: rgba(255, 255, 255, 0.85);
        text-decoration: none;
        transition: color 0.15s ease;
    }
    .edux-brand-footer__contact li a:hover {
        color: var(--theme-yellow, #f4ed2d);
    }

    /* Social Icons */
    .edux-brand-footer__socials {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .edux-brand-footer__socials a {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(34, 141, 221, 0.35);
        color: #ffffff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        text-decoration: none;
        transition: all 0.15s ease;
    }
    .edux-brand-footer__socials a:hover {
        background: var(--theme-yellow, #f4ed2d);
        border-color: var(--theme-yellow, #f4ed2d);
        color: #04153b;
        transform: translateY(-2px);
    }

    /* Footer Bottom Bar */
    .edux-brand-footer__bottom {
        border-top: 1px solid rgba(34, 141, 221, 0.22);
        padding-top: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }
    .edux-brand-footer__copy {
        font-size: 12px;
        color: rgba(255, 255, 255, 0.65);
        margin: 0;
    }
    .edux-brand-footer__bottom-links {
        display: flex;
        align-items: center;
        gap: 18px;
    }
    .edux-brand-footer__bottom-links a {
        font-size: 12px;
        color: rgba(255, 255, 255, 0.7);
        text-decoration: none;
        transition: color 0.15s ease;
    }
    .edux-brand-footer__bottom-links a:hover {
        color: var(--theme-yellow, #f4ed2d);
    }

    /* Hide duplicate page-level newsletter strips since footer now includes it globally */
    .edux-newsletter-strip {
        display: none !important;
    }

    /* ==========================================================================
       4. Shared Inner Page Components (Subscriptions, Events, About, Instructors, Contact)
       ========================================================================== */
    .edux-page-wrap {
        background-color: #f8fafc;
        min-height: 75vh;
        padding: 36px 0 72px;
        font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        color: #0f172a;
    }
    .edux-page-hero {
        background: linear-gradient(135deg, var(--theme) 0%, var(--theme-2, #228ddd) 100%);
        padding: 48px 0 54px;
        color: #ffffff;
        position: relative;
        overflow: hidden;
    }
    .edux-page-hero__eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255, 255, 255, 0.16);
        border: 1px solid rgba(255, 255, 255, 0.25);
        color: #ffffff;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        padding: 4px 12px;
        border-radius: 999px;
        margin-bottom: 12px;
    }
    .edux-page-hero__title {
        font-size: 32px;
        font-weight: 800;
        color: #ffffff;
        letter-spacing: -0.02em;
        line-height: 1.22;
        margin: 0 0 10px;
    }
    .edux-page-hero__title span {
        color: var(--theme-yellow, #f4ed2d);
    }
    .edux-page-hero__desc {
        font-size: 14px;
        color: rgba(255, 255, 255, 0.88);
        line-height: 1.6;
        max-width: 640px;
        margin: 0;
    }

    /* Clean Catalog Top Header (Used on Events, Instructors) */
    .edux-inner-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 28px;
    }
    .edux-inner-header__eyebrow {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--theme);
        margin-bottom: 4px;
    }
    .edux-inner-header__title {
        font-size: 28px;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.02em;
        line-height: 1.2;
        margin: 0 0 4px;
    }
    .edux-inner-header__title span {
        color: var(--theme);
    }
    .edux-inner-header__sub {
        font-size: 13.5px;
        color: #64748b;
        margin: 0;
    }

    /* White Elevated Surface Card */
    .edux-surface-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03);
        height: 100%;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }
    .edux-surface-card--hover:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
        border-color: #cbd5e1;
    }

    /* Global Catalog & Event Cards (.edux-catalog-card) */
    .edux-catalog-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        height: 100%;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }
    .edux-catalog-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
        border-color: #cbd5e1;
    }
    .edux-catalog-card__thumb {
        position: relative;
        height: 175px;
        background: #0f172a;
        overflow: hidden;
    }
    .edux-catalog-card__thumb-link {
        display: block;
        width: 100%;
        height: 100%;
    }
    .edux-catalog-card__thumb-link img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.35s ease;
    }
    .edux-catalog-card:hover .edux-catalog-card__thumb-link img {
        transform: scale(1.04);
    }
    .edux-catalog-card__badges-tl {
        position: absolute;
        top: 10px;
        left: 10px;
        right: 64px;
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        z-index: 2;
        pointer-events: none;
    }
    .edux-catalog-card__badge-cat {
        background: rgba(255, 255, 255, 0.96);
        color: #0f172a;
        font-size: 10.5px;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 999px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
    }
    .edux-catalog-card__badge-sub {
        background: var(--theme);
        color: #ffffff;
        font-size: 10px;
        font-weight: 600;
        padding: 3px 9px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .edux-catalog-card__badges-tr {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 2;
    }
    .edux-catalog-card__badge-free {
        background: #10b981;
        color: #ffffff;
        font-size: 10.5px;
        font-weight: 700;
        padding: 3px 9px;
        border-radius: 999px;
    }
    .edux-catalog-card__badge-discount {
        background: #ef4444;
        color: #ffffff;
        font-size: 10.5px;
        font-weight: 700;
        padding: 3px 9px;
        border-radius: 999px;
    }
    .edux-catalog-card__wish-btn {
        position: absolute;
        bottom: 10px;
        left: 10px;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.95);
        color: #0f172a;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        text-decoration: none;
    }
    .edux-catalog-card__body {
        padding: 14px 16px 16px;
        display: flex;
        flex-direction: column;
        flex: 1;
    }
    .edux-catalog-card__rating-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 7px;
    }
    .edux-catalog-card__stars {
        display: inline-flex;
        align-items: center;
        gap: 2px;
        font-size: 11px;
    }
    .edux-catalog-card__stars .is-filled { color: #f59e0b; }
    .edux-catalog-card__stars .is-empty { color: #e2e8f0; }
    .edux-catalog-card__rating-num {
        font-size: 12px;
        font-weight: 700;
        color: #334155;
        margin-left: 4px;
    }
    .edux-catalog-card__students {
        font-size: 11.5px;
        color: #64748b;
    }
    .edux-catalog-card__title {
        font-size: 14.5px;
        font-weight: 700;
        line-height: 1.38;
        margin: 0 0 9px;
        color: #0f172a;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 40px;
    }
    .edux-catalog-card__title a {
        color: inherit;
        text-decoration: none;
    }
    .edux-catalog-card__title a:hover {
        color: var(--theme);
    }
    .edux-catalog-card__instructor {
        display: flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 10px;
    }
    .edux-catalog-card__avatar {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        object-fit: cover;
    }
    .edux-catalog-card__avatar-initials {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: var(--theme);
        color: #ffffff;
        font-size: 9px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .edux-catalog-card__instructor-name {
        font-size: 12px;
        color: #64748b;
    }
    .edux-catalog-card__meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        font-size: 11.5px;
        color: #64748b;
        padding-bottom: 12px;
        margin-bottom: 12px;
        border-bottom: 1px solid #f1f5f9;
    }
    .edux-catalog-card__meta span {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .edux-catalog-card__footer {
        margin-top: auto;
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 8px;
    }
    .edux-catalog-card__sub-note {
        font-size: 10.5px;
        font-weight: 600;
        color: #059669;
        margin-bottom: 3px;
    }
    .edux-catalog-card__price-row {
        display: flex;
        align-items: baseline;
        gap: 6px;
    }
    .edux-catalog-card__price {
        font-size: 16px;
        font-weight: 800;
        color: #0f172a;
    }
    .edux-catalog-card__price.is-free {
        color: #059669;
    }
    .edux-catalog-card__price-old {
        font-size: 12px;
        color: #94a3b8;
        text-decoration: line-through;
    }
    .edux-catalog-card__view-btn {
        border: 1.5px solid var(--theme);
        background: #ffffff;
        color: var(--theme);
        border-radius: 8px;
        padding: 5px 15px;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
    }
    .edux-catalog-card__view-btn:hover {
        background: var(--theme);
        color: #ffffff;
    }

    /* Modern Form Controls (Contact Page) */
    .edux-form-label {
        display: block;
        font-size: 12.5px;
        font-weight: 700;
        color: #334155;
        margin-bottom: 6px;
    }
    .edux-form-input,
    .edux-form-textarea {
        width: 100%;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 13.5px;
        color: #0f172a;
        background: #f8fafc;
        outline: none;
        transition: border-color 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
    }
    .edux-form-input:focus,
    .edux-form-textarea:focus {
        border-color: var(--theme);
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(1, 59, 166, 0.08);
    }
    .edux-btn-primary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        height: 42px;
        padding: 0 22px;
        border-radius: 10px;
        border: 0;
        background: var(--theme);
        color: #ffffff !important;
        font-size: 13.5px;
        font-weight: 700;
        text-decoration: none !important;
        cursor: pointer;
        transition: background-color 0.15s ease, transform 0.15s ease;
    }
    .edux-btn-primary:hover {
        background: var(--theme-2, #228ddd);
        color: #ffffff !important;
        transform: translateY(-1px);
    }
    .edux-btn-outline {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        height: 36px;
        padding: 0 16px;
        border-radius: 8px;
        border: 1.5px solid var(--theme);
        background: #ffffff;
        color: var(--theme) !important;
        font-size: 12.5px;
        font-weight: 600;
        text-decoration: none !important;
        transition: all 0.15s ease;
    }
    .edux-btn-outline:hover {
        background: var(--theme);
        color: #ffffff !important;
    }

    /* ==========================================================================
       4. Admin & Global Theme Button Overrides
       ========================================================================== */
    .theme-btn i {
        background-color: {{ $accentColor }} !important;
        color: #081c45 !important;
    }
    .theme-btn:hover i {
        background-color: {{ $primaryColor }} !important;
        color: #ffffff !important;
    }
    .bg-primary, .navbar-bg { background-color: {{ $primaryColor }} !important; }
    .text-primary, .text-primary-all *, .text-primary-all *:before, .text-primary-all *:after { color: {{ $primaryColor }} !important; }
    .btn-primary { background-color: {{ $primaryColor }} !important; border-color: {{ $primaryColor }} !important; }
    .btn-primary:hover, .btn-primary:focus, .btn-primary:active { background-color: {{ $secondaryColor }} !important; border-color: {{ $secondaryColor }} !important; }
    .nav-pills .nav-item .nav-link.active { background-color: {{ $primaryColor }}; }
    .custom-control-input:checked ~ .custom-control-label::before {
        background-color: {{ $primaryColor }} !important;
        border-color: {{ $primaryColor }} !important;
    }
    .page-item.active .page-link {
        background-color: {{ $primaryColor }};
        border-color: {{ $primaryColor }};
    }
    .main-sidebar .sidebar-menu li.active a,
    .main-sidebar .sidebar-menu li.active a i,
    .main-sidebar .sidebar-menu li ul.dropdown-menu li.active > a,
    .main-sidebar .sidebar-menu li a:hover {
        color: {{ $primaryColor }};
    }
</style>
