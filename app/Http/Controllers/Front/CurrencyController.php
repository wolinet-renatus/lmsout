<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

/**
 * Switches active display currency in the user session.
 *
 * @author Renatus Bernardo
 */
class CurrencyController extends Controller
{
    /**
     * Switch active session display currency and redirect back.
     */
    public function __invoke(string $code): RedirectResponse
    {
        $normalized = normalize_currency_code($code);
        if (isset(supported_switch_currencies()[$normalized])) {
            session()->put('active_currency', $normalized);
        }

        return back();
    }
}
