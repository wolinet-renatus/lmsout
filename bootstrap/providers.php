<?php

/**
 * Service Provider Registry
 *
 * Lists core application, Fortify authentication, and cookie-consent providers.
 *
 * @author Renatus Bernardo
 */

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\FortifyServiceProvider::class,
    'Spatie\CookieConsent\CookieConsentServiceProvider',
];
