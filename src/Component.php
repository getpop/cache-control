<?php
namespace PoP\CacheControl;

use PoP\Root\Component\AbstractComponent;
use PoP\Root\Component\YAMLServicesTrait;
use PoP\Root\Component\CanDisableComponentTrait;
// use PoP\ComponentModel\Container\ContainerBuilderUtils;
use PoP\CacheControl\DirectiveResolvers\CacheControlDirectiveResolver;
use PoP\ComponentModel\AttachableExtensions\AttachableExtensionGroups;
use PoP\CacheControl\DirectiveResolvers\NestedFieldCacheControlDirectiveResolver;

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
        }
    }

    protected static function resolveEnabled()
    {
        return !Environment::disableCacheControl();
    }

    /**
     * Boot component
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        // Initialize directive resolvers, attaching each of them using the right priorities
        // ContainerBuilderUtils::attachDirectiveResolversFromNamespace(__NAMESPACE__.'\\DirectiveResolvers');
        self::setDirectiveResolverPriorities();
    }

    /**
     * Sets the right priority for the directive resolvers
     *
     * @return void
     */
    protected static function setDirectiveResolverPriorities()
    {
        // It must execute before anyone else!
        NestedFieldCacheControlDirectiveResolver::attach(AttachableExtensionGroups::FIELDDIRECTIVERESOLVERS, PHP_INT_MAX);
        // It must execute last!
        CacheControlDirectiveResolver::attach(AttachableExtensionGroups::FIELDDIRECTIVERESOLVERS, 0);
    }
}
