<?php

namespace App\Services;

use App\Models\PaymentGatewaySetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Pesapal API 3.0 Gateway Service
 *
 * Encapsulates OAuth token retrieval, automatic IPN URL registration,
 * SubmitOrderRequest checkout creation, and GetTransactionStatus verification
 * for both Web and API controllers.
 *
 * @package App\Services
 * @author Renatus Bernardo
 */
class PesapalService
{
    public const SANDBOX_BASE_URL = 'https://cybqa.pesapal.com/pesapalv3';
    public const LIVE_BASE_URL = 'https://pay.pesapal.com/v3';

    public function __construct(private PaymentGatewayService $paymentGatewayService)
    {
    }

    /**
     * Resolve configured Pesapal credentials and active environment mode.
     *
     * @return array<string, mixed>
     */
    public function credentials(): array
    {
        $credentials = $this->paymentGatewayService->credentialsFor('pesapal');

        return [
            'consumer_key'    => trim((string) ($credentials['consumer_key'] ?? '')),
            'consumer_secret' => trim((string) ($credentials['consumer_secret'] ?? '')),
            'ipn_id'          => trim((string) ($credentials['ipn_id'] ?? '')),
            'mode'            => strtolower(trim((string) ($credentials['mode'] ?? 'sandbox'))) === 'live' ? 'live' : 'sandbox',
        ];
    }

    /**
     * Return the Pesapal API 3.0 base URL for the configured mode.
     */
    public function getBaseUrl(?string $mode = null): string
    {
        $resolvedMode = $mode ?? ($this->credentials()['mode'] ?? 'sandbox');

        return $resolvedMode === 'live' ? self::LIVE_BASE_URL : self::SANDBOX_BASE_URL;
    }

    /**
     * Authenticate with Pesapal API 3.0 and return a Bearer access token.
     */
    public function requestToken(bool $fresh = false): string
    {
        $creds = $this->credentials();
        if ($creds['consumer_key'] === '' || $creds['consumer_secret'] === '') {
            throw new RuntimeException('Pesapal credentials (consumer_key / consumer_secret) are not configured.');
        }

        $cacheKey = 'pesapal_bearer_token_' . md5($creds['mode'] . '|' . $creds['consumer_key']);
        if (! $fresh && Cache::has($cacheKey)) {
            $cached = (string) Cache::get($cacheKey);
            if ($cached !== '') {
                return $cached;
            }
        }

        $response = Http::acceptJson()
            ->asJson()
            ->connectTimeout(15)
            ->timeout(30)
            ->post($this->getBaseUrl($creds['mode']) . '/api/Auth/RequestToken', [
                'consumer_key'    => $creds['consumer_key'],
                'consumer_secret' => $creds['consumer_secret'],
            ]);

        $payload = $response->json() ?? [];
        if (! $response->successful() || empty($payload['token'])) {
            $errorMsg = $payload['error']['message']
                ?? $payload['message']
                ?? ('HTTP ' . $response->status());

            throw new RuntimeException('Pesapal authentication failed: ' . $errorMsg);
        }

        $token = (string) $payload['token'];
        // Pesapal tokens are valid for 5 minutes; cache conservatively for 4 minutes.
        Cache::put($cacheKey, $token, now()->addMinutes(4));

        return $token;
    }

    /**
     * Return configured IPN notification UUID or register the dynamic IPN endpoint automatically.
     */
    public function getOrRegisterIpnId(string $ipnUrl): string
    {
        $creds = $this->credentials();
        if ($creds['ipn_id'] !== '') {
            return $creds['ipn_id'];
        }

        $normalizedIpnUrl = rtrim(trim($ipnUrl), '/');
        $cacheKey = 'pesapal_ipn_id_' . md5($creds['mode'] . '|' . $creds['consumer_key'] . '|' . $normalizedIpnUrl);
        $cachedIpnId = trim((string) Cache::get($cacheKey, ''));
        if ($cachedIpnId !== '') {
            return $cachedIpnId;
        }

        $token = $this->requestToken();
        $baseUrl = $this->getBaseUrl($creds['mode']);

        // For publicly reachable URLs, register directly first to avoid downloading massive sandbox IPN lists.
        if ($this->isPublicUrl($ipnUrl)) {
            $registered = $this->tryRegisterIpnUrl($baseUrl, $token, $ipnUrl);
            if ($registered['ipn_id'] !== '') {
                Cache::put($cacheKey, $registered['ipn_id'], now()->addDays(7));
                $this->persistIpnId($registered['ipn_id']);

                return $registered['ipn_id'];
            }

            $listMatch = $this->findIpnIdFromList($baseUrl, $token, $normalizedIpnUrl, false);
            if ($listMatch !== '') {
                Cache::put($cacheKey, $listMatch, now()->addDays(7));
                $this->persistIpnId($listMatch);

                return $listMatch;
            }

            throw new RuntimeException('Failed to register Pesapal IPN URL: ' . ($registered['error'] ?: 'Unknown error'));
        }

        // For local/private URLs (such as localhost dev or tests), inspect GetIpnList first with generous timeout.
        $listMatch = $this->findIpnIdFromList($baseUrl, $token, $normalizedIpnUrl, $creds['mode'] === 'sandbox');
        if ($listMatch !== '') {
            Cache::put($cacheKey, $listMatch, now()->addDays(7));
            $this->persistIpnId($listMatch);

            return $listMatch;
        }

        $registered = $this->tryRegisterIpnUrl($baseUrl, $token, $ipnUrl);
        if ($registered['ipn_id'] !== '') {
            Cache::put($cacheKey, $registered['ipn_id'], now()->addDays(7));
            $this->persistIpnId($registered['ipn_id']);

            return $registered['ipn_id'];
        }

        throw new RuntimeException(
            'Failed to register Pesapal IPN URL: '
            . ($registered['error'] ?: 'No usable IPN ID found. Set your IPN ID in Gateway Settings or deploy to a public domain.')
        );
    }

    /**
     * Create a Pesapal API 3.0 order and return order_tracking_id, merchant_reference, and redirect_url.
     *
     * @param array<string, mixed> $orderParams
     * @return array<string, string>
     */
    public function submitOrder(array $orderParams): array
    {
        $creds = $this->credentials();
        $token = $this->requestToken();
        $baseUrl = $this->getBaseUrl($creds['mode']);

        $ipnUrl = (string) ($orderParams['ipn_url'] ?? route('payment.pesapal.ipn'));
        $notificationId = ! empty($orderParams['notification_id'])
            ? (string) $orderParams['notification_id']
            : $this->getOrRegisterIpnId($ipnUrl);

        $fullName = trim((string) ($orderParams['customer_name'] ?? 'Student'));
        $nameParts = preg_split('/\s+/', $fullName) ?: ['Student'];
        $firstName = $this->sanitizePesapalField($nameParts[0] ?? 'Student', 'Student');
        if (count($nameParts) >= 3) {
            $middleName = $this->sanitizePesapalField($nameParts[1], 'NA');
            $lastName = $this->sanitizePesapalField(implode('-', array_slice($nameParts, 2)), $firstName);
        } elseif (count($nameParts) === 2) {
            $middleName = 'NA';
            $lastName = $this->sanitizePesapalField($nameParts[1], $firstName);
        } else {
            $middleName = 'NA';
            $lastName = $firstName;
        }

        $merchantReference = (string) ($orderParams['merchant_reference'] ?? ('PSP-' . time() . '-' . strtoupper(Str::random(6))));
        $phone = preg_replace('/[^0-9+\-]/', '', trim((string) ($orderParams['customer_phone'] ?? '')));
        if ($phone === '') {
            $phone = '0712345678';
        }

        $body = [
            'id'              => Str::limit($merchantReference, 50, ''),
            'currency'        => strtoupper((string) ($orderParams['currency'] ?? currency_code() ?: 'TZS')),
            'amount'          => round((float) $orderParams['amount'], 2),
            'description'     => Str::limit((string) ($orderParams['description'] ?? 'Course Enrollment Payment'), 100, ''),
            'callback_url'    => (string) ($orderParams['callback_url'] ?? route('payment.pesapal.callback')),
            'notification_id' => $notificationId,
            'billing_address' => [
                'email_address' => trim((string) ($orderParams['customer_email'] ?? '')) ?: 'student@out.ac.tz',
                'phone_number'  => $phone,
                'country_code'  => strtoupper((string) ($orderParams['country_code'] ?? 'TZ')),
                'first_name'    => $firstName,
                'middle_name'   => $middleName,
                'last_name'     => $lastName,
                'line_1'        => $this->sanitizePesapalField((string) ($orderParams['line_1'] ?? ''), 'Dar-es-Salaam'),
                'line_2'        => $this->sanitizePesapalField((string) ($orderParams['line_2'] ?? ''), 'Tanzania'),
                'city'          => $this->sanitizePesapalField((string) ($orderParams['city'] ?? ''), 'Dar-es-Salaam'),
                'state'         => $this->sanitizePesapalField((string) ($orderParams['state'] ?? ''), 'Dar-es-Salaam'),
                'postal_code'   => $this->sanitizePesapalField((string) ($orderParams['postal_code'] ?? ''), '14101', 20),
                'zip_code'      => $this->sanitizePesapalField((string) ($orderParams['zip_code'] ?? ''), '14101', 20),
            ],
        ];

        $response = Http::withToken($token)
            ->acceptJson()
            ->asJson()
            ->connectTimeout(15)
            ->timeout(30)
            ->post($baseUrl . '/api/Transactions/SubmitOrderRequest', $body);

        $payload = $response->json() ?? [];
        if (
            ! $response->successful()
            || ! empty($payload['error'])
            || empty($payload['redirect_url'])
            || empty($payload['order_tracking_id'])
        ) {
            $errorMsg = $payload['error']['message']
                ?? $payload['message']
                ?? ('HTTP ' . $response->status());

            throw new RuntimeException('Pesapal SubmitOrderRequest failed: ' . $errorMsg);
        }

        return [
            'order_tracking_id'  => (string) $payload['order_tracking_id'],
            'merchant_reference' => (string) ($payload['merchant_reference'] ?? $body['id']),
            'redirect_url'       => (string) $payload['redirect_url'],
        ];
    }

    /**
     * Fetch live transaction status from Pesapal API 3.0 using orderTrackingId.
     *
     * @return array<string, mixed>
     */
    public function getTransactionStatus(string $orderTrackingId): array
    {
        $creds = $this->credentials();
        $token = $this->requestToken();
        $baseUrl = $this->getBaseUrl($creds['mode']);

        $response = Http::withToken($token)
            ->acceptJson()
            ->connectTimeout(15)
            ->timeout(30)
            ->get($baseUrl . '/api/Transactions/GetTransactionStatus', [
                'orderTrackingId' => $orderTrackingId,
            ]);

        $payload = $response->json() ?? [];
        if (! $response->successful() || ! is_array($payload)) {
            $errorMsg = is_array($payload)
                ? ($payload['error']['message'] ?? $payload['message'] ?? ('HTTP ' . $response->status()))
                : ('HTTP ' . $response->status());

            throw new RuntimeException('Pesapal GetTransactionStatus failed: ' . $errorMsg);
        }

        return $payload;
    }

    /**
     * Determine whether Pesapal status payload indicates a completed payment.
     *
     * @param array<string, mixed> $statusData
     */
    public function isCompletedStatus(array $statusData): bool
    {
        $description = strtoupper((string) ($statusData['payment_status_description'] ?? $statusData['status'] ?? ''));
        $rawCode = $statusData['status_code'] ?? null;
        $statusCode = is_numeric($rawCode) ? (int) $rawCode : -1;

        return $description === 'COMPLETED' || $statusCode === 1;
    }

    /**
     * Determine whether Pesapal status payload indicates a terminal failure or reversal.
     *
     * @param array<string, mixed> $statusData
     */
    public function isFailedStatus(array $statusData): bool
    {
        $description = strtoupper((string) ($statusData['payment_status_description'] ?? $statusData['status'] ?? ''));
        $rawCode = $statusData['status_code'] ?? null;
        $statusCode = is_numeric($rawCode) ? (int) $rawCode : -1;

        return in_array($description, ['FAILED', 'INVALID', 'REVERSED'], true)
            || in_array($statusCode, [0, 2, 3], true);
    }

    /**
     * Save the discovered IPN notification UUID back to PaymentGatewaySetting for reuse.
     */
    protected function persistIpnId(string $ipnId): void
    {
        $setting = PaymentGatewaySetting::where('identifier', 'pesapal')->first();
        if (! $setting) {
            return;
        }

        $credentials = is_array($setting->credentials) ? $setting->credentials : [];
        if (($credentials['ipn_id'] ?? '') === $ipnId) {
            return;
        }

        $credentials['ipn_id'] = $ipnId;
        $setting->update(['credentials' => $credentials]);
        $this->paymentGatewayService->refreshCache();
    }

    /**
     * Attempt to register an IPN URL with Pesapal API 3.0.
     *
     * @return array{ipn_id: string, error: string}
     */
    protected function tryRegisterIpnUrl(string $baseUrl, string $token, string $ipnUrl): array
    {
        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->asJson()
                ->connectTimeout(15)
                ->timeout(30)
                ->post($baseUrl . '/api/URLSetup/RegisterIPN', [
                    'url'                   => $ipnUrl,
                    'ipn_notification_type' => 'GET',
                ]);

            $payload = $response->json() ?? [];
            if ($response->successful() && ! empty($payload['ipn_id']) && empty($payload['error'])) {
                return [
                    'ipn_id' => (string) $payload['ipn_id'],
                    'error'  => '',
                ];
            }

            $errorMsg = $payload['error']['message']
                ?? $payload['message']
                ?? ('HTTP ' . $response->status());

            return [
                'ipn_id' => '',
                'error'  => (string) $errorMsg,
            ];
        } catch (\Throwable $e) {
            return [
                'ipn_id' => '',
                'error'  => $e->getMessage(),
            ];
        }
    }

    /**
     * Search GetIpnList for an active matching IPN URL, or optionally any active sandbox IPN URL.
     */
    protected function findIpnIdFromList(string $baseUrl, string $token, string $normalizedIpnUrl, bool $allowActiveSandboxFallback): string
    {
        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->connectTimeout(15)
                ->timeout(60)
                ->get($baseUrl . '/api/URLSetup/GetIpnList');

            $entries = $response->json();
            if (! $response->successful() || ! is_array($entries)) {
                return '';
            }

            $fallbackId = '';
            foreach ($entries as $entry) {
                if (! is_array($entry) || empty($entry['ipn_id']) || (int) ($entry['ipn_status'] ?? 1) !== 1) {
                    continue;
                }

                if ($fallbackId === '' && $allowActiveSandboxFallback) {
                    $fallbackId = (string) $entry['ipn_id'];
                }

                if (isset($entry['url']) && rtrim((string) $entry['url'], '/') === $normalizedIpnUrl) {
                    return (string) $entry['ipn_id'];
                }
            }

            return $fallbackId;
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Determine whether an IPN URL uses a public, non-local host acceptable to Pesapal RegisterIPN.
     */
    protected function isPublicUrl(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '' || $host === 'localhost' || str_ends_with($host, '.local') || str_ends_with($host, '.test')) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
        }

        return true;
    }

    /**
     * Sanitize a billing address field to match Pesapal's [A-Za-z0-9\-_.:] constraint.
     */
    protected function sanitizePesapalField(string $value, string $fallback, int $maxLength = 50): string
    {
        $cleaned = preg_replace('/\s+/', '-', trim($value));
        $cleaned = preg_replace('/[^A-Za-z0-9\-_.:]/', '', (string) $cleaned);
        $cleaned = trim((string) $cleaned, '-_.');

        if ($cleaned === '') {
            $cleaned = $fallback;
        }

        return Str::limit($cleaned, $maxLength, '');
    }
}
