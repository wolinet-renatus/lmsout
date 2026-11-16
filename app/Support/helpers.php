<?php

use App\Models\FrontendSetting;
use App\Services\SettingsRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Global helper functions for settings, currency/tax formatting, branding, and validation.
 *
 * @package App\Support
 * @author Wolinet
 * @author Renatus Bernardo
 */
if (!function_exists('settings')) {
    /**
     * Settings.
     */
    function settings(?string $key = null, $default = null)
    {
        /** @var SettingsRepository $repository */
        $repository = app(SettingsRepository::class);

        if ($key === null) {
            return $repository;
        }

        return $repository->get($key, $default);
    }
}

if (!function_exists('normalize_currency_code')) {
    /**
     * Normalize currency aliases (e.g., TSH -> TZS, $ -> USD).
     */
    function normalize_currency_code(?string $code, ?string $fallback = null): string
    {
        $normalized = strtoupper(trim((string) $code));

        return match ($normalized) {
            '', 'DEFAULT' => strtoupper(trim((string) ($fallback ?? config('app.currency', 'TZS')))),
            'TSH', 'TZSH' => 'TZS',
            '$', 'US$', 'DOLLAR' => 'USD',
            default => $normalized,
        };
    }
}

if (!function_exists('base_currency_code')) {
    /**
     * Get the platform's configured storage/base currency code.
     */
    function base_currency_code(): string
    {
        return normalize_currency_code(settings('platform.general.default_currency', config('app.currency', 'TZS')), 'TZS');
    }
}

if (!function_exists('usd_to_tzs_rate')) {
    /**
     * Get the configured exchange rate for 1 USD in TZS (default: 2500).
     */
    function usd_to_tzs_rate(): float
    {
        $rate = (float) settings('platform.general.usd_to_tzs_rate', 2500);

        return $rate > 0 ? $rate : 2500.0;
    }
}

if (!function_exists('supported_switch_currencies')) {
    /**
     * Return currencies available in UI quick switchers.
     *
     * @return array<string, string>
     */
    function supported_switch_currencies(): array
    {
        $options = [
            'TZS' => 'TSH (TZS)',
            'USD' => 'USD ($)',
        ];

        $base = base_currency_code();
        if (! isset($options[$base])) {
            $options[$base] = $base;
        }

        return $options;
    }
}

if (!function_exists('active_currency_code')) {
    /**
     * Resolve the active display currency code from query, session, or platform default.
     */
    function active_currency_code(): string
    {
        $supported = supported_switch_currencies();

        if (app()->bound('request')) {
            $reqCurrency = request()->query('currency');
            if ($reqCurrency !== null && $reqCurrency !== '') {
                $normalized = normalize_currency_code((string) $reqCurrency);
                if (isset($supported[$normalized])) {
                    return $normalized;
                }
            }
        }

        if (app()->bound('session.store') && session()->has('active_currency')) {
            $normalized = normalize_currency_code((string) session('active_currency'));
            if (isset($supported[$normalized])) {
                return $normalized;
            }
        }

        return base_currency_code();
    }
}

if (!function_exists('convert_currency')) {
    /**
     * Convert an amount between supported currencies using the configured USD <-> TZS exchange rate.
     */
    function convert_currency($amount, ?string $fromCurrency = null, ?string $toCurrency = null): float
    {
        $numericAmount = (float) $amount;
        $from = normalize_currency_code($fromCurrency, base_currency_code());
        $to = normalize_currency_code($toCurrency, active_currency_code());

        if ($from === $to) {
            return round($numericAmount, 2);
        }

        $rate = usd_to_tzs_rate();

        if ($from === 'USD' && $to === 'TZS') {
            return round($numericAmount * $rate, 2);
        }

        if ($from === 'TZS' && $to === 'USD') {
            return round($numericAmount / $rate, 2);
        }

        return round($numericAmount, 2);
    }
}

if (!function_exists('currency_code')) {
    /**
     * Get the active display currency code.
     */
    function currency_code(): string
    {
        return active_currency_code();
    }
}

if (!function_exists('currency_symbol')) {
    /**
     * Resolve the currency symbol for a given code.
     */
    function currency_symbol(?string $code = null): string
    {
        $code = normalize_currency_code($code ?? currency_code());

        $map = [
            'USD' => '$',
            'TZS' => 'TZS ',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'BDT' => '৳',
            'INR' => '₹',
            'PKR' => '₨',
            'AUD' => 'A$',
            'CAD' => 'C$',
            'SGD' => 'S$',
            'NZD' => 'NZ$',
            'CNY' => '¥',
            'AED' => 'د.إ',
            'SAR' => '﷼',
            'ZAR' => 'R',
            'BRL' => 'R$',
            'MXN' => 'MX$',
            'NGN' => '₦',
            'GHS' => '₵',
            'KES' => 'KSh',
            'UGX' => 'USh',
        ];

        return $map[$code] ?? ($code . ' ');
    }
}

if (!function_exists('tax_enabled')) {
    /**
     * Tax enabled.
     */
    function tax_enabled(): bool
    {
        return (bool) settings('platform.tax.enabled', false);
    }
}

if (!function_exists('tax_name')) {
    /**
     * Tax name.
     */
    function tax_name(): string
    {
        return settings('platform.tax.name', 'VAT');
    }
}

if (!function_exists('tax_rate')) {
    /**
     * Tax rate.
     */
    function tax_rate(): float
    {
        return (float) settings('platform.tax.rate', 0.0);
    }
}

if (!function_exists('tax_number')) {
    /**
     * Tax number.
     */
    function tax_number(): string
    {
        return settings('platform.tax.number', '');
    }
}

if (!function_exists('calculate_tax')) {
    /**
     * Calculate tax.
     */
    function calculate_tax($subtotal): float
    {
        if (!tax_enabled()) {
            return 0.0;
        }
        $rate = tax_rate();
        return round(($subtotal * $rate) / 100, 2);
    }
}

if (!function_exists('currency_format')) {
    /**
     * Format an amount using the active display currency and exchange rate conversion.
     *
     * @param float|int|string $amount
     * @param int $decimals
     * @param string $style 'code', 'symbol', or 'none'
     * @param string|null $sourceCurrency Currency in which $amount is stored (defaults to platform base currency)
     * @param string|null $targetCurrency Display currency override (defaults to active currency)
     * @param bool $convert Whether to convert from $sourceCurrency to $targetCurrency
     */
    function currency_format($amount, int $decimals = 2, string $style = 'code', ?string $sourceCurrency = null, ?string $targetCurrency = null, bool $convert = true): string
    {
        $targetCode = normalize_currency_code($targetCurrency, currency_code());
        $numericAmount = $convert
            ? convert_currency($amount, $sourceCurrency ?? base_currency_code(), $targetCode)
            : (float) $amount;

        $formatted = number_format(abs($numericAmount), $decimals, '.', ',');
        $sign = $numericAmount < 0 ? '-' : '';
        $symbol = currency_symbol($targetCode);

        return match ($style) {
            'symbol' => $sign . $symbol . $formatted,
            'none' => $sign . $formatted,
            default => $sign . $targetCode . ' ' . $formatted,
        };
    }
}

if (!function_exists('platform_settings')) {
    /**
     * Access platform configuration settings.
     */
    function platform_settings(?string $key = null, $default = null)
    {
        $settings = settings('platform.general', []);

        if ($key === null) {
            return $settings;
        }

        return data_get($settings, $key, $default);
    }
}

if (!function_exists('branding_settings')) {
    /**
     * Access branding & assets settings.
     */
    function branding_settings(?string $key = null, $default = null)
    {
        $settings = settings('branding.assets', []);

        if ($key === null) {
            return $settings;
        }

        return data_get($settings, $key, $default);
    }
}

if (!function_exists('frontend_meta')) {
    /**
     * Retrieve stored SEO metadata for a frontend page.
     */
    function frontend_meta(string $slug, ?string $key = null, $default = null)
    {
        $entries = FrontendSetting::getValue('pages.seo.meta', []);
        $entry = $entries[$slug] ?? null;

        if (!$entry) {
            return $default;
        }

        if ($key !== null) {
            return $entry[$key] ?? $default;
        }

        return $entry;
    }
}

if (!function_exists('frontend_setting')) {
    /**
     * Retrieve a frontend setting value from the key-value store.
     */
    function frontend_setting(string $key, $default = null)
    {
        return FrontendSetting::getValue($key, $default);
    }
}

if (!function_exists('custom_page_url')) {
    /**
     * Generate a URL to a custom page by slug.
     */
    function custom_page_url(string $slug): string
    {
        if (Route::has('pages.show')) {
            return route('pages.show', ['customPage' => $slug]);
        }

        return url($slug);
    }
}

if (!function_exists('branding_asset')) {
    /**
     * Resolve a branding asset path (logo, favicon, etc.) with support for legacy keys.
     *
     * @param  string|array  $keys
     * @param  string|null   $fallback
     */
    function branding_asset(string|array $keys, ?string $fallback = null): string
    {
        $branding = branding_settings();
        $keys = (array) $keys;

        $path = null;
        foreach ($keys as $key) {
            $value = data_get($branding, $key);
            if (filled($value)) {
                $path = $value;
                break;
            }
        }

        $url = frontend_media($path, $fallback);
        return $url !== '#' ? $url . (str_contains($url, '?') ? '&v=3' : '?v=3') : $url;
    }
}

if (!function_exists('frontend_media')) {
    /**
     * Resolve media path for frontend usage supporting storage, CDN, and asset fallbacks.
     */
    function frontend_media(?string $path, ?string $fallback = null): string
    {
        if (blank($path)) {
            return $fallback ? asset($fallback) : '#';
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        if (Str::startsWith($path, 'assets/') || file_exists(public_path($path))) {
            return asset($path);
        }

        $normalized = ltrim(str_replace(['storage/', 'public/'], '', $path), '/');

        if ($normalized && Storage::disk('public')->exists($normalized)) {
            return asset('storage/' . $normalized);
        }

        return $fallback ? asset($fallback) : asset($path);
    }
}

if (!function_exists('ensure_storage_link')) {
    /**
     * Ensure the public storage symbolic link exists and is correct.
     */
    function ensure_storage_link()
    {
        $link = public_path('storage');

        // Remove stale directories, files, or dangling symlinks left from another server path.
        if (is_link($link) && !file_exists($link)) {
            @unlink($link);
        } elseif (file_exists($link) && !is_link($link)) {
            if (is_dir($link)) {
                @rmdir($link);
            } else {
                @unlink($link);
            }
        }

        if (!file_exists($link)) {
            \Illuminate\Support\Facades\Artisan::call('storage:link', ['--relative' => true]);
        }
    }
}

if (!function_exists('site_name')) {
    /**
     * Get the globally configured site name.
     */
    function site_name(): string
    {
        return branding_settings('site_name', platform_settings('site_name', config('app.name', 'OUT LMS')));
    }
}

if (!function_exists('site_tagline')) {
    /**
     * Get the globally configured site tagline.
     */
    function site_tagline(): string
    {
        return platform_settings('tagline', 'Affordable and quality education for all');
    }
}

if (!function_exists('custom_scripts')) {
    /**
     * Get custom scripts configuration.
     */
    function custom_scripts(?string $key = null, $default = null)
    {
        $settings = settings('platform.custom_scripts', []);

        if ($key === null) {
            return $settings;
        }

        return data_get($settings, $key, $default);
    }
}

if (!function_exists('active_theme')) {
    /**
     * Get the active homepage theme slug.
     *
     * @return string|null Null means default (home.blade.php).
     */
    function active_theme(): ?string
    {
        return app(\App\Services\ThemeService::class)->getActiveTheme();
    }
}

if (!function_exists('active_variant')) {
    /**
     * Get the active homepage variant file name.
     */
    function active_variant(): string
    {
        return app(\App\Services\ThemeService::class)->getActiveVariant();
    }
}

if (!function_exists('theme_asset')) {
    /**
     * Resolve a theme asset URL.
     *
     * Usage: theme_asset('img/hero/hero-bg.jpg')
     * Returns: "/themes/eduna/img/hero/hero-bg.jpg"
     *
     * @param string      $path  Relative path within the theme's assets folder.
     * @param string|null $theme Theme slug (defaults to active theme).
     */
    function theme_asset(string $path, ?string $theme = null): string
    {
        $themeService = app(\App\Services\ThemeService::class);
        $baseUrl = $themeService->getAssetBaseUrl($theme);

        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('theme_styles')) {
    /**
     * Get HTML tags for all CSS files defined in the active theme's theme.json.
     */
    function theme_styles(): string
    {
        $themeService = app(\App\Services\ThemeService::class);
        $theme = $themeService->getActiveTheme();
        if (!$theme) {
            return '';
        }

        $meta = $themeService->getThemeMeta($theme);
        $cssFiles = $meta['css'] ?? [];

        $html = '';
        foreach ($cssFiles as $css) {
            $url = theme_asset($css, $theme);
            $html .= '    <link rel="stylesheet" href="' . $url . '">' . "\n";
        }
        return $html;
    }
}

if (!function_exists('theme_scripts')) {
    /**
     * Get HTML tags for all JS files defined in the active theme's theme.json.
     */
    function theme_scripts(): string
    {
        $themeService = app(\App\Services\ThemeService::class);
        $theme = $themeService->getActiveTheme();
        if (!$theme) {
            return '';
        }

        $meta = $themeService->getThemeMeta($theme);
        $jsFiles = $meta['js'] ?? [];

        $html = '';
        foreach ($jsFiles as $js) {
            $url = theme_asset($js, $theme);
            $html .= '    <script src="' . $url . '"></script>' . "\n";
        }
        return $html;
    }
}

if (!function_exists('validatePhoneNumber')) {
    /**
     * Normalize and validate Tanzanian/international phone numbers to E.164 (+255...).
     */
    function validatePhoneNumber(?string $phone): string|false
    {
        if (!$phone) {
            return false;
        }

        $clean = preg_replace('/[\s\-().]+/', '', trim($phone));
        if (str_starts_with($clean, '0') && strlen($clean) === 10) {
            $clean = '+255' . substr($clean, 1);
        } elseif (preg_match('/^255\d{9}$/', $clean)) {
            $clean = '+' . $clean;
        } elseif (!str_starts_with($clean, '+') && preg_match('/^[67]\d{8}$/', $clean)) {
            $clean = '+255' . $clean;
        }

        return preg_match('/^\+\d{9,15}$/', $clean) ? $clean : false;
    }
}

if (!function_exists('generateInvoicePrefix')) {
    /**
     * Generate a readable campus invoice number prefix.
     */
    function generateInvoicePrefix(): string
    {
        return 'INV-' . date('Ym') . '-';
    }
}

if (!function_exists('getEducationLevelLabel')) {
    /**
     * Human-readable education level labels matching the SCMS Applicants Form.
     */
    function getEducationLevelLabel(?string $level): string
    {
        $scmsLevels = \App\Models\Applicant::educationLevels();
        if ($level !== null && isset($scmsLevels[(string) $level])) {
            return $scmsLevels[(string) $level];
        }

        return match ($level) {
            'primary' => 'Primary School',
            'secondary' => 'Secondary School (O-Level / A-Level)',
            'certificate' => 'Certificate',
            'diploma' => 'Diploma',
            'bachelor', 'degree' => 'Bachelor\'s Degree',
            'master', 'masters' => 'Master\'s Degree',
            'phd' => 'PhD / Doctorate',
            'other' => 'Other',
            default => $level ? ucwords(str_replace('_', ' ', $level)) : 'Not Specified',
        };
    }
}

if (!function_exists('resolve_app_url')) {
    /**
     * Resolve relative paths and stale local/legacy subdirectory URLs dynamically
     * against the active HTTP request root so links never break across deployments.
     */
    function resolve_app_url(?string $rawUrl, string $fallback = '#'): string
    {
        $rawUrl = $rawUrl !== null ? trim($rawUrl) : '';
        if ($rawUrl === '') {
            return $fallback;
        }

        if (
            $rawUrl === '#'
            || str_starts_with($rawUrl, '#')
            || str_starts_with($rawUrl, 'mailto:')
            || str_starts_with($rawUrl, 'tel:')
            || str_starts_with($rawUrl, 'javascript:')
        ) {
            return $rawUrl;
        }

        if (!preg_match('#^https?://#i', $rawUrl)) {
            $path = '/' . ltrim($rawUrl, '/');
            $path = preg_replace('#^/(?:edux(?:/public)?|lmsout(?:/public)?|public)(?=/|$)#i', '', $path) ?: '/';

            return url($path);
        }

        $parsed = parse_url($rawUrl);
        if (!$parsed || empty($parsed['host'])) {
            return $rawUrl;
        }

        $host = strtolower($parsed['host']);
        $requestHost = (!app()->runningInConsole() && app()->bound('request'))
            ? strtolower((string) request()->getHost())
            : '';

        $path = $parsed['path'] ?? '/';
        $hasLegacyPrefix = (bool) preg_match('#^/(?:edux(?:/public)?|lmsout(?:/public)?|public)(?=/|$)#i', $path);
        $isLocalHost = in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            || ($requestHost !== '' && $host === $requestHost);

        if (!$isLocalHost && !$hasLegacyPrefix) {
            return $rawUrl;
        }

        $cleanPath = preg_replace('#^/(?:edux(?:/public)?|lmsout(?:/public)?|public)(?=/|$)#i', '', $path);
        $cleanPath = '/' . ltrim((string) $cleanPath, '/');

        $resolved = url($cleanPath);
        if (!empty($parsed['query'])) {
            $resolved .= '?' . $parsed['query'];
        }
        if (!empty($parsed['fragment'])) {
            $resolved .= '#' . $parsed['fragment'];
        }

        return $resolved;
    }
}
