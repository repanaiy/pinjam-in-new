<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $fillable = ['key', 'value', 'description', 'updated_at'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = Cache::rememberForever('settings.all', fn () => static::query()->pluck('value', 'key')->all());

        return $all[$key] ?? $default;
    }

    public static function put(string $key, string $value): void
    {
        static::query()->updateOrInsert(['key' => $key], ['value' => $value, 'updated_at' => now()]);
        Cache::forget('settings.all');
    }
}
