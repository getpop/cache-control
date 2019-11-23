<?php
namespace PoP\CacheControl\DirectiveResolvers;

use PoP\CacheControl\Environment;
use PoP\Translation\Facades\TranslationAPIFacade;
use PoP\ComponentModel\FieldResolvers\FieldResolverInterface;

class CacheControlDirectiveResolver extends AbstractCacheControlDirectiveResolver
{
    public function getSchemaDirectiveDescription(FieldResolverInterface $fieldResolver): ?string
    {
        $translationAPI = TranslationAPIFacade::getInstance();
        return sprintf(
            $translationAPI->__('%1$s %2$s'),
            sprintf(
                $translationAPI->__('Default \'%1$s\' directive, used if no other %1$s directive can process the affected fields.', 'cache-control'),
                $this::getDirectiveName()
            ),
            parent::getSchemaDirectiveDescription($fieldResolver)
        );
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
