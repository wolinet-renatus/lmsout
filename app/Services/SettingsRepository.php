<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Settings Repository
 *
 * This repository is responsible for managing the platform settings.
 *
 * @package App\Services
 * @author Renatus Bernardo
 */
class SettingsRepository
{
    /**
     * All.
     */
    public function all()
    {
        return PlatformSetting::all()->mapWithKeys(function (PlatformSetting $setting) {
            return [$setting->key => $this->decodePayload($setting)];
        })->toArray();
    }

    /**
     * Get.
     */
    public function get(string $key, $default = null)
    {
        [$baseKey, $nested] = $this->splitKey($key);

        $setting = PlatformSetting::where('key', $baseKey)->first();

        if (!$setting) {
            return $default;
        }

        $data = $this->decodePayload($setting);

        if ($nested) {
            return Arr::get($data, $nested, $default);
        }

        return $data ?? $default;
    }

    /**
     * For group.
     */
    public function forGroup(string $group): array
    {
        return PlatformSetting::where('group', $group)
            ->get()
            ->mapWithKeys(function (PlatformSetting $setting) {
                return [$setting->key => $this->decodePayload($setting)];
            })->toArray();
    }

    /**
     * Set.
     */
    public function set(string $key, $value, string $group, string $type = 'string', bool $encrypted = false): PlatformSetting
    {
        $payload = $encrypted
            ? ['encrypted' => $this->encryptPayload($value)]
            : ['data' => $value];

        return PlatformSetting::updateOrCreate(
            ['key' => $key],
            [
                'group' => $group,
                'type' => $type,
                'payload' => $payload,
                'is_encrypted' => $encrypted,
            ]
        );
    }

    /**
     * Update group.
     */
    public function updateGroup(string $group, array $values): void
    {
        foreach ($values as $key => $config) {
            $value = $config['value'] ?? $config['data'] ?? null;
            $type = $config['type'] ?? 'string';
            $encrypted = (bool) ($config['encrypted'] ?? false);

            $this->set($key, $value, $group, $type, $encrypted);
        }
    }

    /**
     * Decode payload.
     */
    protected function decodePayload(PlatformSetting $setting)
    {
        if ($setting->is_encrypted) {
            $encrypted = $setting->payload['encrypted'] ?? null;

            if (!$encrypted) {
                return null;
            }

            try {
                $json = Crypt::decryptString($encrypted);
                return json_decode($json, true);
            } catch (Throwable $exception) {
                Log::warning('Failed to decrypt platform setting', [
                    'key' => $setting->key,
                    'message' => $exception->getMessage(),
                ]);

                return null;
            }
        }

        return $setting->payload['data'] ?? null;
    }

    /**
     * Encrypt payload.
     */
    protected function encryptPayload($value): string
    {
        $json = json_encode($value);

        return Crypt::encryptString($json ?: 'null');
    }

    /**
     * Split key.
     */
    protected function splitKey(string $key): array
    {
        if (str_contains($key, '.')) {
            $segments = explode('.', $key);

            while (count($segments) > 1) {
                $base = implode('.', array_slice($segments, 0, count($segments) - 1));
                $nested = implode('.', array_slice($segments, count($segments) - 1));

                if (PlatformSetting::where('key', $base)->exists()) {
                    return [$base, $nested];
                }

                array_pop($segments);
            }
        }

        return [$key, null];
    }
}
