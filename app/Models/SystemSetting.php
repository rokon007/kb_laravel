<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group_name',
    ];

    protected $casts = [
        'updated_at' => 'datetime',
    ];

    // Boot method to clear cache on update
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            Cache::forget('system_settings');
        });

        static::deleted(function () {
            Cache::forget('system_settings');
        });
    }

    // Helper Methods

    /**
     * Get setting value by key
     */
    public static function get(string $key, $default = null)
    {
        $settings = Cache::rememberForever('system_settings', function () {
            return self::all()->pluck('value', 'key');
        });

        return $settings->get($key, $default);
    }

    /**
     * Set setting value
     */
    public static function set(string $key, $value, string $type = 'string', string $group = null)
    {
        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'group_name' => $group,
            ]
        );
    }

    /**
     * Get all settings as array
     */
    public static function getAll(): array
    {
        return Cache::rememberForever('system_settings', function () {
            return self::all()->pluck('value', 'key')->toArray();
        });
    }

    /**
     * Get settings by group
     */
    public static function getByGroup(string $group): array
    {
        return self::where('group_name', $group)
            ->pluck('value', 'key')
            ->toArray();
    }
}