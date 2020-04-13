<?php

declare(strict_types=1);

namespace PoP\CacheControl\Facades;

use PoP\CacheControl\Managers\CacheControlManagerInterface;
use PoP\Root\Container\ContainerBuilderFactory;

class CacheControlManagerFacade
{
    public static function getInstance(): CacheControlManagerInterface
    {
        return ContainerBuilderFactory::getInstance()->get('cache_control_manager');
    }
}
