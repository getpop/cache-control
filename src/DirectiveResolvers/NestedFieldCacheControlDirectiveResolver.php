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
            return $this->isAnyFieldArgumentValueAField($fieldArgElems);
        }
        return false;
    }

    protected function isAnyFieldArgumentValueAField($fieldArgValue): bool
    {
        $fieldQueryInterpreter = FieldQueryInterpreterFacade::getInstance();
        $fieldArgValue = $fieldQueryInterpreter->maybeConvertFieldArgumentValue($fieldArgValue);
        // If it is an array, we must evaluate if any of its items is a field
        if (is_array($fieldArgValue)) {
            return array_reduce(
                (array)$fieldArgValue,
                function($carry, $item) {
                    return $carry || $this->isAnyFieldArgumentValueAField($item);
                },
                false
            );
        }
        return $fieldQueryInterpreter->isFieldArgumentValueAField($fieldArgValue);
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
            $fieldDirectiveFields = array_unique(array_merge(
                $nestedFields,
                array_map(
                    // To evaluate on the root fields, we must remove the fieldArgs, to avoid a loop
                    [$fieldQueryInterpreter, 'getFieldName'],
                    $fields
                )
            ));
            $fieldDirectiveResolverInstances = $fieldResolver->getDirectiveResolverInstanceForDirective(
                $this->directive,
                $fieldDirectiveFields
            );
            // Nothing to do, there's some error
            if (is_null($fieldDirectiveResolverInstances)) {
                return;
            }
            // Consolidate the same directiveResolverInstances for different fields, as to execute them only once
            $directiveResolverInstanceFieldsDataItems = [];
            foreach ($fieldDirectiveResolverInstances as $field => $directiveResolverInstance) {
                if (is_null($directiveResolverInstance)) {
                    continue;
                }

                $instanceID = get_class($directiveResolverInstance);
                if (!isset($directiveResolverInstanceFieldsDataItems[$instanceID])) {
                    $directiveResolverInstanceFieldsDataItems[$instanceID]['instance'] = $directiveResolverInstance;
                }
                $directiveResolverInstanceFieldsDataItems[$instanceID]['fields'][] = $field;
            }
            // Iterate through all the directives, and simply resolve each
            foreach ($directiveResolverInstanceFieldsDataItems as $instanceID => $directiveResolverInstanceFieldsDataItem) {
                $directiveResolverInstance = $directiveResolverInstanceFieldsDataItem['instance'];
                $directiveResolverFields = $directiveResolverInstanceFieldsDataItem['fields'];

                // Regenerate the $idsDataFields for each directive
                $directiveResolverIDDataFields = [];
                foreach (array_keys($idsDataFields) as $id) {
                    $directiveResolverIDDataFields[(string)$id] = [
                        'direct' => $directiveResolverFields,
                    ];
                }
                $directiveResolverInstance->resolveDirective($dataloader, $fieldResolver, $directiveResolverIDDataFields, $succeedingPipelineIDsDataFields, $resultIDItems, $dbItems, $previousDBItems, $variables, $messages, $dbErrors, $dbWarnings, $schemaErrors, $schemaWarnings, $schemaDeprecations);
            }
            // That's it, we are done!
            return;
        }

        return parent::resolveDirective($dataloader, $fieldResolver, $idsDataFields, $succeedingPipelineIDsDataFields, $resultIDItems, $dbItems, $previousDBItems, $variables, $messages, $dbErrors, $dbWarnings, $schemaErrors, $schemaWarnings, $schemaDeprecations);
    }
}
