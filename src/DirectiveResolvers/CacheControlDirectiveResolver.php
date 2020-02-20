<?php
namespace PoP\CacheControl\DirectiveResolvers;

use PoP\CacheControl\Environment;

class CacheControlDirectiveResolver extends AbstractCacheControlDirectiveResolver
{
    /**
     * Because this directive will be implemented several times, make its schema definition be added only once
     *
     * @return void
     */
    public function skipAddingToSchemaDefinition() {
        return false;
    }

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
