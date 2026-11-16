<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentGatewaySetting;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Services\PaymentGatewayService;

/**
 * Payment Gateway Controller
 *
 * This controller handles the management of payment gateways.
 *
 * @package App\Http\Controllers\Admin
 * @author Renatus Bernardo
 */
class PaymentGatewayController extends Controller
{
    /**
     * Display a listing of the resource records.
     */
    public function index(): View
    {
        $gateways = PaymentGatewaySetting::orderBy('type')->orderBy('name')->get();

        $credentialFieldMap = [
            'stripe' => ['key', 'secret'],
            'flutterwave' => ['public_key', 'secret_key'],
            'paypal' => ['client_id', 'client_secret', 'mode'],
            'pesapal' => ['consumer_key', 'consumer_secret', 'ipn_id', 'mode'],
        ];

        return view('admin.settings.gateways.index', compact('gateways', 'credentialFieldMap'));
    }

    /**
     * Validate and update the specified resource record.
     */
    public function update(Request $request, string $identifier): RedirectResponse
    {
        $gateway = PaymentGatewaySetting::where('identifier', $identifier)->firstOrFail();

        $validated = $request->validate([
            'is_enabled' => ['nullable', 'boolean'],
            'offline_instructions' => ['nullable', 'string'],
            'credentials' => ['nullable', 'array'],
        ]);

        $gateway->is_enabled = (bool) ($validated['is_enabled'] ?? false);

        if ($gateway->type === 'offline') {
            $gateway->offline_instructions = $validated['offline_instructions'] ?? $gateway->offline_instructions;
        } else {
            $credentials = $validated['credentials'] ?? [];
            $gateway->credentials = collect($credentials)
                ->map(fn($value) => is_string($value) ? trim($value) : $value)
                ->filter(fn($value) => $value !== null && $value !== '')
                ->toArray();
        }

        $gateway->save();

        app(PaymentGatewayService::class)->refreshCache();

        ToastMagic::success('Gateway settings updated successfully.');

        return redirect()->route('admin.settings.gateways.index');
    }
}
