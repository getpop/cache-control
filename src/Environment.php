<?php
namespace PoP\CacheControl;

class Environment
{
    public static function disableCacheControl(): bool
    {
        return isset($_ENV['DISABLE_CACHE_CONTROL']) ? strtolower($_ENV['DISABLE_CACHE_CONTROL']) == "true" : false;
    }

    public static function getDefaultCacheControlMaxAge(): int
    {
        // If not set, use 1 minute by default
        return isset($_ENV['DEFAULT_CACHE_CONTROL_MAX_AGE']) ? (int)$_ENV['DEFAULT_CACHE_CONTROL_MAX_AGE'] : 60;
    }
}
