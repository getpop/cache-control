<?php
namespace PoP\CacheControl\DirectiveResolvers;

use PoP\CacheControl\Environment;

class CacheControlDirectiveResolver extends AbstractCacheControlDirectiveResolver
{
    /**
     * Do add this directive to the schema
     *
     * @return void
     */
    public function skipAddingToSchemaDefinition(): bool {
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
