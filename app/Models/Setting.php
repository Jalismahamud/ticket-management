<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = self::allCached();

        if (array_key_exists($key, $settings)) {
            $value = $settings[$key];
            if (is_numeric($value)) {
                return str_contains($value, '.') ? (float) $value : (int) $value;
            }
            if (is_string($value) && (str_starts_with($value, '[') || str_starts_with($value, '{'))) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
            }
            return $value;
        }

        return $default;
    }

    public static function set(string $key, mixed $value, ?string $group = 'general'): self
    {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }

        $setting = self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group ?? 'general']
        );

        Cache::forget('system_settings');

        return $setting;
    }

    public static function allCached(): array
    {
        return Cache::rememberForever('system_settings', function () {
            try {
                if (!\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                    return [];
                }
                return self::pluck('value', 'key')->toArray();
            } catch (\Throwable) {
                return [];
            }
        });
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('system_settings'));
        static::deleted(fn () => Cache::forget('system_settings'));
    }
}
