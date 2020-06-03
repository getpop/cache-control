<?php

declare(strict_types=1);

namespace PoP\CacheControl;

class Environment
{
    public const DEFAULT_CACHE_CONTROL_MAX_AGE = 'DEFAULT_CACHE_CONTROL_MAX_AGE';

    public static function disableCacheControl(): bool
    {
        return isset($_ENV['DISABLE_CACHE_CONTROL']) ? strtolower($_ENV['DISABLE_CACHE_CONTROL']) == "true" : false;
    }
}
