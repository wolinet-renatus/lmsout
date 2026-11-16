@php
    $themeColors = \Illuminate\Support\Facades\Schema::hasTable('frontend_settings')
        ? \App\Models\FrontendSetting::getValue('theme.colors', [])
        : [];
    $brandPrimary = $themeColors['primary'] ?? branding_settings('primary_color', '#013ba6');
    $brandSecondary = $themeColors['secondary'] ?? branding_settings('secondary_color', '#228ddd');
    $brandAccent = branding_settings('accent_color', $themeColors['accent'] ?? '#f4ed2d');
    $siteName = site_name();
    $primaryLogoUrl = branding_asset(
        ['primary_logo_path', 'logos.primary', 'dark_logo_path', 'logos.secondary'],
        'assets/front/img/logo/brand-logo-v3.png'
    );
    $secondaryLogoUrl = branding_asset(
        ['dark_logo_path', 'logos.secondary', 'primary_logo_path', 'logos.primary'],
        'assets/front/img/logo/brand-logo-v3.png'
    );
@endphp
<style>
    :root {
        --inv-primary: {{ $brandPrimary }};
        --inv-secondary: {{ $brandSecondary }};
        --inv-accent: {{ $brandAccent }};
        --inv-ink: #0f172a;
        --inv-muted: #52637a;
        --inv-tint: #f3f7fd;
    }

    * {
        box-sizing: border-box;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    body.lmsout-doc-body {
        background: linear-gradient(180deg, #edf3fb 0%, #f8fafc 100%);
        color: var(--inv-ink);
        padding: 28px 16px;
        font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        margin: 0;
    }

    .lmsout-doc-sheet {
        position: relative;
        max-width: 880px;
        margin: 0 auto;
        background: #ffffff;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 18px 46px rgba(1, 59, 166, 0.14);
        border-left: 7px solid var(--inv-primary);
        border-right: 7px solid var(--inv-secondary);
    }

    .lmsout-doc-edge-top {
        height: 10px;
        width: 100%;
        background: linear-gradient(
            90deg,
            var(--inv-primary) 0%,
            var(--inv-primary) 62%,
            var(--inv-secondary) 62%,
            var(--inv-secondary) 86%,
            var(--inv-accent) 86%,
            var(--inv-accent) 100%
        );
    }

    .lmsout-doc-edge-bottom {
        height: 9px;
        width: 100%;
        background: linear-gradient(
            90deg,
            var(--inv-accent) 0%,
            var(--inv-accent) 14%,
            var(--inv-secondary) 14%,
            var(--inv-secondary) 38%,
            var(--inv-primary) 38%,
            var(--inv-primary) 100%
        );
    }

    .lmsout-doc-inner {
        position: relative;
        padding: 28px 34px 26px;
        background:
            linear-gradient(180deg, var(--inv-primary), var(--inv-secondary)) left 12px top 18px / 3px calc(100% - 36px) no-repeat,
            linear-gradient(180deg, var(--inv-secondary), var(--inv-primary)) right 12px top 18px / 3px calc(100% - 36px) no-repeat;
    }

    .lmsout-doc-watermark {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        pointer-events: none;
        z-index: 0;
    }

    .lmsout-doc-watermark img {
        width: 260px;
        height: 260px;
        object-fit: contain;
        opacity: 0.045;
        filter: grayscale(20%);
    }

    .lmsout-doc-content {
        position: relative;
        z-index: 1;
    }

    .lmsout-doc-header {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 18px;
        padding: 18px 20px;
        margin-bottom: 24px;
        background: linear-gradient(135deg, #f5f9ff 0%, #ffffff 55%, #eef6ff 100%);
        border: 1px solid rgba(1, 59, 166, 0.14);
        border-top: 3px solid var(--inv-primary);
        border-bottom: 3px solid var(--inv-secondary);
        border-radius: 10px;
    }

    .lmsout-doc-side-left {
        display: flex;
        align-items: center;
        gap: 14px;
        flex: 1 1 280px;
    }

    .lmsout-doc-side-right {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 14px;
        flex: 1 1 260px;
        text-align: right;
    }

    .lmsout-doc-logo-frame {
        width: 68px;
        height: 68px;
        flex-shrink: 0;
        border-radius: 12px;
        background: #ffffff;
        padding: 6px;
        border: 2px solid rgba(1, 59, 166, 0.22);
        box-shadow: 0 4px 12px rgba(1, 59, 166, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .lmsout-doc-logo-frame--right {
        border-color: rgba(34, 141, 221, 0.35);
    }

    .lmsout-doc-logo-frame img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        display: block;
    }

    .lmsout-doc-brand-name {
        color: var(--inv-primary);
        font-size: 1.28rem;
        font-weight: 800;
        letter-spacing: -0.01em;
        line-height: 1.15;
        margin: 0 0 4px;
    }

    .lmsout-doc-type-badge {
        display: inline-block;
        background: var(--inv-primary);
        color: #ffffff;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        padding: 4px 10px;
        border-radius: 999px;
        margin-bottom: 6px;
        border-bottom: 2px solid var(--inv-accent);
    }

    .lmsout-doc-control-pill {
        display: inline-block;
        margin-top: 4px;
        padding: 3px 10px;
        border-radius: 6px;
        background: rgba(34, 141, 221, 0.1);
        border: 1px solid rgba(34, 141, 221, 0.3);
        color: var(--inv-primary);
        font-size: 0.84rem;
        font-weight: 700;
    }

    .lmsout-doc-section-label {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--inv-secondary);
    }

    .lmsout-doc-table thead th {
        background: var(--inv-primary) !important;
        color: #ffffff !important;
        border-color: var(--inv-primary) !important;
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .lmsout-doc-balance-row td {
        background: rgba(1, 59, 166, 0.08) !important;
        color: var(--inv-primary) !important;
        border-top: 2px solid var(--inv-primary) !important;
        border-bottom: 2px solid var(--inv-secondary) !important;
    }

    .lmsout-doc-footer-strip {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: 24px;
        padding: 12px 16px;
        border-radius: 8px;
        background: var(--inv-tint);
        border-left: 4px solid var(--inv-primary);
        border-right: 4px solid var(--inv-secondary);
        font-size: 0.78rem;
        color: var(--inv-muted);
    }

    .lmsout-doc-footer-mini-logo {
        height: 28px;
        width: 28px;
        object-fit: contain;
        border-radius: 6px;
        background: #ffffff;
        padding: 2px;
        border: 1px solid rgba(1, 59, 166, 0.2);
    }

    @media print {
        @page {
            margin: 10mm;
        }
        body.lmsout-doc-body {
            background: #ffffff !important;
            padding: 0 !important;
        }
        .lmsout-doc-sheet {
            box-shadow: none !important;
            border-radius: 0 !important;
            max-width: 100% !important;
        }
        .no-print,
        .d-print-none {
            display: none !important;
        }
    }
</style>
