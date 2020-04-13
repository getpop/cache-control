<?php

declare(strict_types=1);

namespace PoP\CacheControl\DirectiveResolvers;

use PoP\FieldQuery\QueryHelpers;
use PoP\ComponentModel\Misc\GeneralUtils;
// use PoP\CacheControl\Schema\SchemaDefinition;
// use PoP\Translation\Facades\TranslationAPIFacade;
use PoP\ComponentModel\TypeResolvers\TypeResolverInterface;
use PoP\ComponentModel\Facades\Schema\FieldQueryInterpreterFacade;

class NestedFieldCacheControlDirectiveResolver extends AbstractCacheControlDirectiveResolver
{
    // public function getSchemaDirectiveDescription(TypeResolverInterface $typeResolver): ?string
    // {
    //     $translationAPI = TranslationAPIFacade::getInstance();
    //     return sprintf(
    //         $translationAPI->__('%1$s %2$s'),
    //         $translationAPI->__('Helper directive to calculate the Cache Control header when the field composes other fields.', 'cache-control'),
    //         parent::getSchemaDirectiveDescription($typeResolver)
    //     );
    // }

    // protected function addSchemaDefinitionForDirective(array &$schemaDefinition)
    // {
    //     $translationAPI = TranslationAPIFacade::getInstance();
    //     $schemaDefinition[SchemaDefinition::ARGNAME_MAX_AGE] = $translationAPI->__('The minimum max-age calculated among the affected fields and all their composed fields.', 'cache-control');
    // }

    /**
     * If any argument is a field, then this directive will involve them to calculate the minimum max-age
     *
     * @param TypeResolverInterface $typeResolver
     * @param string $directiveName
     * @param array $directiveArgs
     * @return boolean
     */
    public function resolveCanProcess(TypeResolverInterface $typeResolver, string $directiveName, array $directiveArgs, string $field, array &$variables): bool
    {
        $fieldQueryInterpreter = FieldQueryInterpreterFacade::getInstance();
        if ($fieldArgs = $fieldQueryInterpreter->getFieldArgs($field)) {
            $fieldArgElems = QueryHelpers::getFieldArgElements($fieldArgs);
            return $this->isFieldArgumentValueAFieldOrAnArrayWithAField($fieldArgElems, $variables);
        }
        return false;
    }

    protected function isFieldArgumentValueAFieldOrAnArrayWithAField($fieldArgValue, array &$variables): bool
    {
        $fieldQueryInterpreter = FieldQueryInterpreterFacade::getInstance();
        $fieldArgValue = $fieldQueryInterpreter->maybeConvertFieldArgumentValue($fieldArgValue, $variables);
        // If it is an array, we must evaluate if any of its items is a field
        if (is_array($fieldArgValue)) {
            return array_reduce(
                (array)$fieldArgValue,
                function ($carry, $item) use ($variables) {
                    return $carry || $this->isFieldArgumentValueAFieldOrAnArrayWithAField($item, $variables);
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
     * Calculate the max-age involving also the composed fields
     *
     * @param array $idsDataFields
     * @return integer
     */
    public function resolveDirective(TypeResolverInterface $typeResolver, array &$idsDataFields, array &$succeedingPipelineIDsDataFields, array &$succeedingPipelineDirectiveResolverInstances, array &$resultIDItems, array &$unionDBKeyIDs, array &$dbItems, array &$previousDBItems, array &$variables, array &$messages, array &$dbErrors, array &$dbWarnings, array &$dbDeprecations, array &$schemaErrors, array &$schemaWarnings, array &$schemaDeprecations)
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
            // Extract all the field arguments which are fields or have fields themselves
            $fieldArgElems = array_unique(GeneralUtils::arrayFlatten(array_map(
                function ($field) use ($fieldQueryInterpreter) {
                    if ($fieldArgs = $fieldQueryInterpreter->getFieldArgs($field)) {
                        return QueryHelpers::getFieldArgElements($fieldArgs);
                    }
                    return [];
                },
                $fields
            )));
            // If any element is an array represented as a string, like "[time()]" when doing /?query=extract(echo([time()]),0), then extract it and merge it into the main array
            $nestedFields = array_unique(GeneralUtils::arrayFlatten(
                (array)$fieldQueryInterpreter->maybeConvertFieldArgumentArrayValue($fieldArgElems),
                true
            ));
            // Extract the composed fields which are either a field, or an array which contain a field
            $nestedFields = array_filter(
                $nestedFields,
                function ($fieldArgValue) use ($variables) {
                    return $this->isFieldArgumentValueAFieldOrAnArrayWithAField($fieldArgValue, $variables);
                }
            );
            $fieldDirectiveFields = array_unique(array_merge(
                $nestedFields,
                array_map(
                    // To evaluate on the root fields, we must remove the fieldArgs, to avoid a loop
                    [$fieldQueryInterpreter, 'getFieldName'],
                    $fields
                )
            ));
            $fieldDirectiveResolverInstances = $typeResolver->getDirectiveResolverInstanceForDirective(
                $this->directive,
                $fieldDirectiveFields,
                $variables
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
                $directiveResolverInstance->resolveDirective($typeResolver, $directiveResolverIDDataFields, $succeedingPipelineIDsDataFields, $succeedingPipelineDirectiveResolverInstances, $resultIDItems, $unionDBKeyIDs, $dbItems, $previousDBItems, $variables, $messages, $dbErrors, $dbWarnings, $dbDeprecations, $schemaErrors, $schemaWarnings, $schemaDeprecations);
            }
            // That's it, we are done!
            return;
        }

        return parent::resolveDirective($typeResolver, $idsDataFields, $succeedingPipelineIDsDataFields, $succeedingPipelineDirectiveResolverInstances, $resultIDItems, $unionDBKeyIDs, $dbItems, $previousDBItems, $variables, $messages, $dbErrors, $dbWarnings, $dbDeprecations, $schemaErrors, $schemaWarnings, $schemaDeprecations);
    }
}
