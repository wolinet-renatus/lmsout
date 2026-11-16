<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SettingsRepository;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;

/**
 * Setting controller API Controller — handles public and authenticated Sanctum REST endpoints.
 *
 * @author Renatus Bernardo
 */
class SettingController extends Controller
{
    protected $repository;
    protected $paymentGatewayService;

    /**
     * Initialize dependencies for this class.
     */
    public function __construct(SettingsRepository $repository, PaymentGatewayService $paymentGatewayService)
    {
        $this->repository = $repository;
        $this->paymentGatewayService = $paymentGatewayService;
    }

    /**
     * Get all public settings.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $settings = [
            'platform' => $this->repository->get('platform.general'),
            'branding' => $this->repository->get('branding.assets'),
            'contact' => $this->repository->get('contact.details'),
            'withdrawals' => $this->repository->get('withdrawals.settings'),
            'tax' => $this->repository->get('tax.settings', [
                'enabled' => false,
                'name' => 'VAT',
                'rate' => 0,
                'type' => 'exclusive',
                'registration_number' => '',
            ]),
            'live_class' => [
                'default_provider' => $this->repository->get('live_class.settings.default_provider', 'zoom'),
                'zoom_enabled' => (bool) $this->repository->get('live_class.settings.zoom_enabled', true),
                'google_meet_enabled' => (bool) $this->repository->get('live_class.settings.google_meet_enabled', true),
            ],
            'app_home_layout' => $this->repository->get('platform.general.app_home_layout', 'home_2'),
            'homepage_variant' => $this->repository->get('frontend.theme.homepage_variant', 'classic'),
        ];

        // Ensure branding assets have full URLs
        if (isset($settings['branding'])) {
            $settings['branding']['primary_logo_url'] = branding_asset('primary_logo_path');
            $settings['branding']['dark_logo_url'] = branding_asset('dark_logo_path');
            $settings['branding']['favicon_url'] = branding_asset('favicon_path');
        }

        // Format currency data for the app
        $currencyCode = $settings['platform']['default_currency'] ?? currency_code();
        $settings['currency'] = [
            'code' => $currencyCode,
            'symbol' => currency_symbol($currencyCode),
        ];

        $settings['payment_methods'] = $this->paymentGatewayService->onlineGateways()->map(function ($gateway) {
            return [
                'identifier' => $gateway->identifier,
                'name' => $gateway->name,
                'description' => $gateway->description,
                'type' => $gateway->type,
                'is_enabled' => $gateway->is_enabled,
            ];
        })->values();

        $offlineGateway = $this->paymentGatewayService->offlineGateway();
        if ($offlineGateway && $offlineGateway->is_enabled) {
            $settings['payment_methods'][] = [
                'identifier' => $offlineGateway->identifier,
                'name' => $offlineGateway->name,
                'description' => $offlineGateway->description,
                'type' => $offlineGateway->type,
                'is_enabled' => $offlineGateway->is_enabled,
                'instructions' => $offlineGateway->offline_instructions,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }
}
