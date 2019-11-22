<?php
namespace PoP\CacheControl\DirectiveResolvers;

use PoP\ComponentModel\DataloaderInterface;
use PoP\ComponentModel\GeneralUtils;
use PoP\FieldQuery\FieldQueryUtils;
use PoP\ComponentModel\FieldResolvers\FieldResolverInterface;
use PoP\ComponentModel\Facades\Schema\FieldQueryInterpreterFacade;
use PoP\FieldQuery\QueryHelpers;

class NestedFieldCacheControlDirectiveResolver extends AbstractCacheControlDirectiveResolver
{
    /**
     * If any argument is a field, then this directive will involve them to calculate the minimum max-age
     *
     * @param FieldResolverInterface $fieldResolver
     * @param string $directiveName
     * @param array $directiveArgs
     * @return boolean
     */
    public function resolveCanProcess(FieldResolverInterface $fieldResolver, string $directiveName, array $directiveArgs = [], string $field): bool
    {
        $fieldQueryInterpreter = FieldQueryInterpreterFacade::getInstance();
        if ($fieldArgs = $fieldQueryInterpreter->getFieldArgs($field)) {
            $fieldArgElems = QueryHelpers::getFieldArgElements($fieldArgs);
            return FieldQueryUtils::isAnyFieldArgumentValueAField($fieldArgElems);
        }
        return false;
    }

    public function getMaxAge(): ?int
    {
        // This value doesn't really matter, it will never be called anyway
        return null;
    }

    /**
     * Calculate the max-age involving also the nested fields
     *
     * @param array $idsDataFields
     * @return integer
     */
    public function resolveDirective(DataloaderInterface $dataloader, FieldResolverInterface $fieldResolver, array &$idsDataFields, array &$succeedingPipelineIDsDataFields, array &$resultIDItems, array &$dbItems, array &$previousDBItems, array &$variables, array &$messages, array &$dbErrors, array &$dbWarnings, array &$schemaErrors, array &$schemaWarnings, array &$schemaDeprecations)
    {
        if ($idsDataFields) {
            $fieldQueryInterpreter = FieldQueryInterpreterFacade::getInstance();
            // Iterate through all the arguments, calculate the maxAge for each of them, and then return the minimum value from all of them and the directiveName for this field
            $fields = [];
            foreach ($idsDataFields as $id => $dataFields) {
                $fields = array_merge(
                    $fields,
                    $dataFields['direct']
                );
            }
            $fields = array_values(array_unique($fields));
            // Extract all the field arguments which are fields themselves
            $fieldArgElems = array_unique(GeneralUtils::arrayFlatten(array_map(
                function($field) use($fieldQueryInterpreter) {
                    if ($fieldArgs = $fieldQueryInterpreter->getFieldArgs($field)) {
                        return QueryHelpers::getFieldArgElements($fieldArgs);
                    }
                    return [];
                },
                $fields
            )));
            $nestedFields = array_filter(
                $fieldArgElems,
                [$fieldQueryInterpreter, 'isFieldArgumentValueAField']
            );
            $directiveResolverInstances = $fieldResolver->getDirectiveResolverInstanceForDirective(
                $this->directive,
                array_unique(array_merge(
                    $nestedFields,
                    array_map(
                        // To evaluate on the root fields, we must remove the fieldArgs, to avoid a loop
                        [$fieldQueryInterpreter, 'getFieldName'],
                        $fields
                    )
                ))
            );
            // Iterate through all the directives, and simply resolve for each
            foreach ($directiveResolverInstances as $directiveResolverInstance) {
                // Each directive implements CacheControlDirectiveResolverInterface
                $directiveResolverInstance->resolveCacheControlDirective();
            }
            // That's it, we are done!
            return;
        }

        return parent::resolveDirective($dataloader, $fieldResolver, $idsDataFields, $succeedingPipelineIDsDataFields, $resultIDItems, $dbItems, $previousDBItems, $variables, $messages, $dbErrors, $dbWarnings, $schemaErrors, $schemaWarnings, $schemaDeprecations);
    }
}
