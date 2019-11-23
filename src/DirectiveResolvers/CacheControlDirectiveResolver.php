<?php
namespace PoP\CacheControl\DirectiveResolvers;

use PoP\CacheControl\Environment;

class CacheControlDirectiveResolver extends AbstractCacheControlDirectiveResolver
{
    /**
     * The default max-age is configured through an environment variable
     *
     * @return integer|null
     */
    public function getMaxAge(): ?int
    {
        return Environment::getDefaultCacheControlMaxAge();
    }
}
