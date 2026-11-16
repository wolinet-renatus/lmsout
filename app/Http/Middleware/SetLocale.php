<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Set Locale Middleware
 *
 * Resolves the active UI language from query string, session, user preference,
 * or system default and shares supported language packs with public/portal views.
 *
 * @package App\Http\Middleware
 * @author Renatus Bernardo
 */
class SetLocale
{
    /**
     * Get the dynamic list of supported locales from resources/lang directories.
     */
    public static function getSupportedLocales(): array
    {
        return \Illuminate\Support\Facades\Cache::rememberForever('active_system_locales', function () {
            return app(\App\Services\LanguageManagerService::class)->getSupportedLocales();
        });
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supportedCurrencies = supported_switch_currencies();
        if ($request->filled('currency')) {
            $requestedCurrency = normalize_currency_code((string) $request->query('currency'));
            if (isset($supportedCurrencies[$requestedCurrency])) {
                session()->put('active_currency', $requestedCurrency);
            }
        }

        $activeCurrency = active_currency_code();
        View::share('activeCurrency', $activeCurrency);
        View::share('currencyCode', $activeCurrency);
        View::share('currencySymbol', currency_symbol($activeCurrency));
        View::share('exchangeRateUsdToTzs', usd_to_tzs_rate());

        // Skip locale switching for admin routes — admin area stays in the default locale
        if ($request->is('admin/*') || $request->routeIs('admin.*')) {
            return $next($request);
        }

        // Priority: query param > session > user preference > default
        $locale = $this->resolveLocale($request);

        // Persist the choice in the session
        if ($request->has('lang')) {
            session()->put('locale', $locale);
        }

        App::setLocale($locale);

        // Share locale info with all views for the language switcher
        View::share('currentLocale', $locale);
        View::share('availableLocales', self::getSupportedLocales());

        return $next($request);
    }

    /**
     * Resolve the locale from the request context.
     */
    protected function resolveLocale(Request $request): string
    {
        // 1. Explicit query parameter  ?lang=bn
        if ($request->has('lang') && $this->isSupported($request->query('lang'))) {
            return $request->query('lang');
        }

        // 2. Session (persisted from a previous switch)
        if (session()->has('locale') && $this->isSupported(session('locale'))) {
            return session('locale');
        }

        // 3. Authenticated user preference (if the column exists)
        if ($request->user() && !empty($request->user()->locale) && $this->isSupported($request->user()->locale)) {
            return $request->user()->locale;
        }

        // 4. Fall back to the app default
        return config('app.locale', 'en');
    }

    /**
     * Check whether the locale code is supported.
     */
    protected function isSupported(?string $locale): bool
    {
        return $locale && array_key_exists($locale, self::getSupportedLocales());
    }
}
