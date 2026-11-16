<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Frontend Setting Model
 *
 * This model represents a setting for the frontend.
 *
 * @package App\Models
 * @author Renatus Bernardo
 */
class FrontendSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    /**
     * Get value.
     */
    public static function getValue(string $key, $default = null)
    {
        $value = optional(self::query()->where('key', $key)->first())->value ?? $default;

        return is_array($value) ? self::resolveStoredUrls($value) : $value;
    }

    /**
     * Normalize stored relative or legacy localhost/subdirectory URLs in setting arrays.
     */
    private static function resolveStoredUrls(array $items): array
    {
        foreach ($items as $k => $v) {
            if (is_array($v)) {
                $items[$k] = self::resolveStoredUrls($v);
            } elseif (
                is_string($v)
                && is_string($k)
                && $k !== 'embed_url'
                && ($k === 'url' || $k === 'href' || str_ends_with($k, '_url'))
            ) {
                $items[$k] = resolve_app_url($v, '');
            }
        }

        return $items;
    }

    /**
     * Set value.
     */
    public static function setValue(string $key, $value): self
    {
        return self::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
