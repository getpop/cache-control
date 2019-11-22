<?php
namespace PoP\CacheControl;

use PoP\Root\Component\AbstractComponent;
use PoP\CacheControl\Config\ServiceConfiguration;
use PoP\Root\Component\CanDisableComponentTrait;
use PoP\Root\Component\YAMLServicesTrait;

/**
 * Initialize component
 */
class Component extends AbstractComponent
{
    // const VERSION = '0.1.0';
    use YAMLServicesTrait, CanDisableComponentTrait;

    /**
     * Initialize services
     */
    public static function init()
    {
        if (self::isEnabled()) {
            parent::init();
            self::initYAMLServices(dirname(__DIR__));
            ServiceConfiguration::init();
        }
    }

    protected static function resolveEnabled()
    {
        return !Environment::disableCacheControl();
    }
}
