<?php

namespace App\Services;

use App\Models\ConfigSystem;
use Illuminate\Support\Facades\Cache;

class ConfigSystemService
{
    private const CACHE_PREFIX = 'config_system:';

    public function get(string $clave, ?string $default = null): ?string
    {
        return Cache::rememberForever(
            self::CACHE_PREFIX.$clave,
            fn () => ConfigSystem::where('clave', $clave)->value('valor') ?? $default,
        );
    }

    public function set(string $clave, string $valor): void
    {
        ConfigSystem::updateOrCreate(['clave' => $clave], ['valor' => $valor]);

        Cache::forever(self::CACHE_PREFIX.$clave, $valor);
    }
}
