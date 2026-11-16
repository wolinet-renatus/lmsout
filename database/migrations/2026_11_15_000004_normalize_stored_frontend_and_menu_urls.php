<?php

/**
 * Database migration: normalize hardcoded localhost/legacy subdirectory URLs in
 * menu_items and frontend_settings to clean relative paths so links auto-resolve
 * dynamically across any server or base path.
 *
 * @author Renatus Bernardo
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('menu_items')) {
            $menuItems = DB::table('menu_items')->whereNotNull('url')->get();

            foreach ($menuItems as $item) {
                $normalized = $this->toRelativePath((string) $item->url);
                if ($normalized !== $item->url) {
                    DB::table('menu_items')
                        ->where('id', $item->id)
                        ->update(['url' => $normalized]);
                }
            }
        }

        if (Schema::hasTable('frontend_settings')) {
            $settings = DB::table('frontend_settings')->get();

            foreach ($settings as $setting) {
                $decoded = json_decode((string) $setting->value, true);
                if (! is_array($decoded)) {
                    continue;
                }

                $updated = $this->normalizeSettingArray($decoded, $changed);
                if ($changed) {
                    DB::table('frontend_settings')
                        ->where('id', $setting->id)
                        ->update(['value' => json_encode($updated)]);
                }
            }
        }

        Cache::forget('frontend.menus.locations');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Relative paths remain valid regardless of environment; no destructive rollback required.
    }

    /**
     * Convert legacy local/subdirectory URLs into clean root-relative paths.
     */
    private function toRelativePath(string $url): string
    {
        $trimmed = trim($url);
        if (! preg_match('#^https?://#i', $trimmed)) {
            return $url;
        }

        $parsed = parse_url($trimmed);
        $host = strtolower((string) ($parsed['host'] ?? ''));
        $path = (string) ($parsed['path'] ?? '/');

        $isLegacyLocal = in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            || preg_match('#^/(?:edux(?:/public)?|lmsout(?:/public)?|public)(?=/|$)#i', $path);

        if (! $isLegacyLocal) {
            return $url;
        }

        $relative = preg_replace('#^/(?:edux(?:/public)?|lmsout(?:/public)?|public)(?=/|$)#i', '', $path);
        $relative = '/' . ltrim((string) $relative, '/');

        if (! empty($parsed['query'])) {
            $relative .= '?' . $parsed['query'];
        }
        if (! empty($parsed['fragment'])) {
            $relative .= '#' . $parsed['fragment'];
        }

        return $relative;
    }

    /**
     * Recursively normalize URL-valued keys inside decoded frontend settings.
     */
    private function normalizeSettingArray(array $data, ?bool &$changed = false): array
    {
        $changed = false;

        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $nestedChanged = false;
                $data[$key] = $this->normalizeSettingArray($val, $nestedChanged);
                if ($nestedChanged) {
                    $changed = true;
                }
            } elseif (
                is_string($val)
                && is_string($key)
                && $key !== 'embed_url'
                && ($key === 'url' || $key === 'href' || str_ends_with($key, '_url'))
            ) {
                $normalized = $this->toRelativePath($val);
                if ($normalized !== $val) {
                    $data[$key] = $normalized;
                    $changed = true;
                }
            }
        }

        return $data;
    }
};
