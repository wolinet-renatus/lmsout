<?php

namespace Database\Seeders;

use App\Models\PaymentGatewaySetting;
use Illuminate\Database\Seeder;

/**
 * Payment gateway setting seeder — seeds baseline institutional, RBAC, or reference records.
 *
 * @author Renatus Bernardo
 */
class PaymentGatewaySettingSeeder extends Seeder
{
    /**
     * Execute the database seeder.
     */
    public function run(): void
    {
        $gateways = [
            [
                'identifier' => 'stripe',
                'name' => 'Stripe',
                'type' => 'online',
                'credentials' => [
                    'key' => env('STRIPE_KEY'),
                    'secret' => env('STRIPE_SECRET'),
                ],
            ],
            [
                'identifier' => 'flutterwave',
                'name' => 'Flutterwave',
                'type' => 'online',
                'credentials' => [
                    'public_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
                    'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
                ],
            ],
            [
                'identifier' => 'paypal',
                'name' => 'PayPal',
                'type' => 'online',
                'credentials' => [
                    'client_id' => env('PAYPAL_CLIENT_ID'),
                    'client_secret' => env('PAYPAL_CLIENT_SECRET'),
                    'mode' => env('PAYPAL_MODE', 'sandbox'),
                ],
            ],
            [
                'identifier' => 'pesapal',
                'name' => 'Pesapal',
                'type' => 'online',
                'credentials' => [
                    'consumer_key' => env('PESAPAL_CONSUMER_KEY', 'ngW+UEcnDhltUc5fxPfrCD987xMh3Lx8'),
                    'consumer_secret' => env('PESAPAL_CONSUMER_SECRET', 'q27RChYs5UkypdcNYKzuUw460Dg='),
                    'ipn_id' => env('PESAPAL_IPN_ID', ''),
                    'mode' => env('PESAPAL_MODE', 'sandbox'),
                ],
            ],
            [
                'identifier' => 'offline',
                'name' => 'Offline Payments',
                'type' => 'offline',
                'credentials' => null,
                'offline_instructions' => "Please complete your payment via bank transfer and upload the receipt. Our finance team will verify within 24 hours.",
            ],
        ];

        foreach ($gateways as $gateway) {
            PaymentGatewaySetting::updateOrCreate(
                ['identifier' => $gateway['identifier']],
                [
                    'name' => $gateway['name'],
                    'type' => $gateway['type'],
                    'credentials' => $gateway['credentials'] ?? null,
                    'is_enabled' => true,
                    'offline_instructions' => $gateway['offline_instructions'] ?? null,
                ]
            );
        }
    }
}
