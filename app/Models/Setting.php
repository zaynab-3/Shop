<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'is_public'];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function publicMap(): array
    {
        return static::query()
            ->where('is_public', true)
            ->pluck('value', 'key')
            ->all();
    }
}
