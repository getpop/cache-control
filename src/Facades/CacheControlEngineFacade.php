<?php
namespace PoP\CacheControl\Facades;

use PoP\CacheControl\Managers\CacheControlEngineInterface;
use PoP\Root\Container\ContainerBuilderFactory;

class CacheControlEngineFacade
{
    public static function getInstance(): CacheControlEngineInterface
    {
        return ContainerBuilderFactory::getInstance()->get('cache_control_engine');
    }
}
