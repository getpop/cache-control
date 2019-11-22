<?php
namespace PoP\CacheControl;

class Environment
{
    public static function disableCacheControl()
    {
        return isset($_ENV['DISABLE_CACHE_CONTROL']) ? strtolower($_ENV['DISABLE_CACHE_CONTROL']) == "true" : false;
    }
}

