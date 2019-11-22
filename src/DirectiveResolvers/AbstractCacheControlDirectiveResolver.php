<?php
namespace PoP\CacheControl\DirectiveResolvers;

use PoP\ComponentModel\DataloaderInterface;
use PoP\CacheControl\Schema\SchemaDefinition;
use PoP\Translation\Facades\TranslationAPIFacade;
use PoP\CacheControl\Facades\CacheControlManagerFacade;
use PoP\ComponentModel\FieldResolvers\FieldResolverInterface;
use PoP\ComponentModel\DirectiveResolvers\AbstractGlobalDirectiveResolver;

abstract class AbstractCacheControlDirectiveResolver extends AbstractGlobalDirectiveResolver implements CacheControlDirectiveResolverInterface
{
    const DIRECTIVE_NAME = 'cacheControl';
    public static function getDirectiveName(): string {
        return self::DIRECTIVE_NAME;
    }

    public function getSchemaDirectiveDescription(FieldResolverInterface $fieldResolver): ?string
    {
        $translationAPI = TranslationAPIFacade::getInstance();
        return $translationAPI->__('Cache the request through HTTP caching (https://tools.ietf.org/html/rfc7234). Set the cache control headers on a field-by-field basis; the overall cache max-age for the requested page will be calculated from all the requested fields', 'component-model');
    }
    public function getSchemaDirectiveArgs(FieldResolverInterface $fieldResolver): array
    {
        $translationAPI = TranslationAPIFacade::getInstance();
        return [
            [
                SchemaDefinition::ARGNAME_NAME => 'maxAge',
                SchemaDefinition::ARGNAME_TYPE => SchemaDefinition::TYPE_INT,
                SchemaDefinition::ARGNAME_DESCRIPTION => $translationAPI->__('Use a specific max-age value for the field, instead of the one configured in the directive', 'translate-directive'),
            ],
        ];
    }

    protected function addSchemaDefinitionForDirective(array &$schemaDefinition)
    {
        // Further add for which providers it works
        if ($maxAge = $this->getMaxAge()) {
            $schemaDefinition[SchemaDefinition::ARGNAME_MAX_AGE] = $maxAge;
        }
    }

    /**
     * Get the cache control for this field, and set it on the Engine
     *
     * @param FieldResolverInterface $fieldResolver
     * @param array $resultIDItems
     * @param array $idsDataFields
     * @param array $dbItems
     * @param array $dbErrors
     * @param array $dbWarnings
     * @param array $schemaErrors
     * @param array $schemaWarnings
     * @param array $schemaDeprecations
     * @return void
     */
    public function resolveDirective(DataloaderInterface $dataloader, FieldResolverInterface $fieldResolver, array &$idsDataFields, array &$succeedingPipelineIDsDataFields, array &$resultIDItems, array &$dbItems, array &$previousDBItems, array &$variables, array &$messages, array &$dbErrors, array &$dbWarnings, array &$schemaErrors, array &$schemaWarnings, array &$schemaDeprecations)
    {
        $this->resolveCacheControlDirective();
    }

    public function resolveCacheControlDirective(): void
    {
        // Set the max age from this field into the service which will calculate the max age for the request, based on all fields
        // If it was provided as a directiveArg, use that value. Otherwise, use the one from the class
        $maxAge = $this->directiveArgsForSchema['maxAge'] ?? $this->getMaxAge();
        if (!is_null($maxAge)) {
            $cacheControlManager = CacheControlManagerFacade::getInstance();
            $cacheControlManager->addMaxAge($maxAge);
        }
    }
}
