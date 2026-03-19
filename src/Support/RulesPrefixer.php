<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support;

use GraphQL\Type\Definition\InputObjectField;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type as GraphqlType;
use LogicException;

/**
 * Transforms cross-field rule references (e.g. `prohibits:otherField`) to use
 * fully-qualified dot-notation paths, so Laravel's Validator resolves them
 * correctly when rules are nested inside InputTypes (especially list types).
 *
 * @internal This class is an implementation detail and not part of the public API.
 *
 * @see https://github.com/rebing/graphql-laravel/issues/930
 */
class RulesPrefixer
{
    /**
     * Rules where ALL parameters are field references.
     *
     * @var list<string>
     */
    protected const CATEGORY_A_RULES = [
        'prohibits',
        'required_with',
        'required_with_all',
        'required_without',
        'required_without_all',
        'present_with',
        'present_with_all',
        'missing_with',
        'missing_with_all',
        'exclude_with',
        'exclude_without',
        'same',
        'different',
    ];

    /**
     * Rules where only the first parameter is a field reference.
     *
     * @var list<string>
     */
    protected const CATEGORY_B_RULES = [
        'required_if',
        'required_unless',
        'prohibited_if',
        'prohibited_unless',
        'exclude_if',
        'exclude_unless',
        'accepted_if',
        'declined_if',
        'present_if',
        'present_unless',
        'missing_if',
        'missing_unless',
        'required_if_accepted',
        'required_if_declined',
        'prohibited_if_accepted',
        'prohibited_if_declined',
    ];

    /**
     * Rules where the first parameter is a field reference OR a literal value.
     * We attempt sibling matching (if it matches a sibling name, prefix it).
     *
     * @var list<string>
     */
    protected const CATEGORY_C_RULES = [
        'gt',
        'gte',
        'lt',
        'lte',
        'before',
        'after',
        'before_or_equal',
        'after_or_equal',
    ];

    /**
     * Apply cross-field rule prefixing to all collected rules.
     *
     * @param array<string,mixed> $rules The collected rules keyed by dot-notation path
     * @param array<string,mixed> $args The Field's args() definition (type tree root)
     * @return array<string,mixed>
     */
    public static function apply(array $rules, array $args): array
    {
        $result = [];

        foreach ($rules as $ruleKey => $ruleValue) {
            $result[$ruleKey] = self::transformRuleValue($ruleKey, $ruleValue, $args);
        }

        return $result;
    }

    /**
     * @param mixed $ruleValue
     * @param array<string,mixed> $args
     * @return mixed
     */
    protected static function transformRuleValue(string $ruleKey, $ruleValue, array $args)
    {
        // Determine the sibling field names for this rule key's context
        $siblingNames = self::findSiblingNames($ruleKey, $args);

        if (!$siblingNames) {
            return $ruleValue;
        }

        $parentPrefix = self::getParentPrefix($ruleKey);

        if (\is_string($ruleValue)) {
            return self::transformRuleString($ruleValue, $siblingNames, $parentPrefix);
        }

        if (\is_array($ruleValue)) {
            return array_map(function ($rule) use ($siblingNames, $parentPrefix) {
                if (\is_string($rule)) {
                    return self::transformRuleString($rule, $siblingNames, $parentPrefix);
                }

                // Rule objects (e.g. Laravel Rule instances) are left untouched
                return $rule;
            }, $ruleValue);
        }

        // Anything else (closures already resolved at this point, objects, etc.)
        return $ruleValue;
    }

    /**
     * Transform a single rule string like "prohibits:mintParams,otherField"
     * by prefixing sibling field references with the parent path.
     *
     * @param list<string> $siblingNames
     */
    protected static function transformRuleString(string $rule, array $siblingNames, string $parentPrefix): string
    {
        // Split on the first colon to get rule name and parameters
        $parts = explode(':', $rule, 2);
        $ruleName = $parts[0];

        if (!isset($parts[1])) {
            return $rule;
        }

        $ruleNameLower = strtolower($ruleName);
        $params = $parts[1];

        if (\in_array($ruleNameLower, self::CATEGORY_A_RULES, true)) {
            $params = self::prefixAllParams($params, $siblingNames, $parentPrefix);
        } elseif (\in_array($ruleNameLower, self::CATEGORY_B_RULES, true) ||
            \in_array($ruleNameLower, self::CATEGORY_C_RULES, true)) {
            // Category B: first param is always a field ref (e.g. required_if:field,value)
            // Category C: first param may be a field ref or literal (e.g. gt:field or gt:100)
            // Both are handled identically — maybePrefixParam() only prefixes known siblings
            $params = self::prefixFirstParam($params, $siblingNames, $parentPrefix);
        } else {
            return $rule;
        }

        return $ruleName . ':' . $params;
    }

    /**
     * Prefix all comma-separated parameters that match sibling names.
     *
     * @param list<string> $siblingNames
     */
    protected static function prefixAllParams(string $params, array $siblingNames, string $parentPrefix): string
    {
        $paramList = explode(',', $params);

        $paramList = array_map(function (string $param) use ($siblingNames, $parentPrefix): string {
            return self::maybePrefixParam($param, $siblingNames, $parentPrefix);
        }, $paramList);

        return implode(',', $paramList);
    }

    /**
     * Prefix only the first parameter if it matches a sibling name.
     *
     * @param list<string> $siblingNames
     */
    protected static function prefixFirstParam(string $params, array $siblingNames, string $parentPrefix): string
    {
        $paramList = explode(',', $params);
        $paramList[0] = self::maybePrefixParam($paramList[0], $siblingNames, $parentPrefix);

        return implode(',', $paramList);
    }

    /**
     * Prefix a parameter with the parent path if it's a plain sibling field name.
     *
     * A parameter is considered a sibling reference only when:
     * 1. It contains no dots (not already a qualified path)
     * 2. It matches one of the sibling field names at the same level
     *
     * @param list<string> $siblingNames
     */
    protected static function maybePrefixParam(string $param, array $siblingNames, string $parentPrefix): string
    {
        $param = trim($param);

        // Already contains dots → already a qualified path, leave it alone
        if (str_contains($param, '.')) {
            return $param;
        }

        // Only prefix if it matches a known sibling field name
        if (\in_array($param, $siblingNames, true)) {
            return $parentPrefix . '.' . $param;
        }

        return $param;
    }

    /**
     * Extract the parent prefix from a rule key by removing the last segment.
     *
     * For "recipients.0.createParams", returns "recipients.0".
     * For "topLevelField", returns "".
     */
    protected static function getParentPrefix(string $ruleKey): string
    {
        $lastDot = strrpos($ruleKey, '.');

        if (false === $lastDot) {
            throw new LogicException(
                "getParentPrefix() received a single-segment key '$ruleKey'; "
                . 'this should have been filtered out by findSiblingNames()',
            );
        }

        return substr($ruleKey, 0, $lastDot);
    }

    /**
     * Find the sibling field names for a given rule key by walking the args type tree.
     *
     * For rule key "recipients.0.createParams", we walk:
     *   args → "recipients" (unwrap NonNull/List) → InputObjectType fields
     * and return the field names of that InputObjectType.
     *
     * @param array<string,mixed> $args The Field's args() definition
     * @return list<string>
     */
    protected static function findSiblingNames(string $ruleKey, array $args): array
    {
        // Split the key into path segments
        $segments = explode('.', $ruleKey);

        // We need at least 2 segments (parent + field) to have siblings within a nested type.
        // Top-level args don't need prefixing because they're already at the root.
        if (\count($segments) < 2) {
            return [];
        }

        // Remove the last segment (the field itself); we want the parent context
        array_pop($segments);

        // Walk the type tree following non-numeric segments
        $currentFields = $args;

        foreach ($segments as $segment) {
            // Skip numeric segments (array indices in list types)
            if (ctype_digit($segment)) {
                continue;
            }

            // Find the arg/field definition for this segment
            $fieldDef = $currentFields[$segment] ?? null;

            if (null === $fieldDef) {
                return [];
            }

            // Extract the GraphQL type from the field definition
            $type = self::extractType($fieldDef);

            if (null === $type) {
                return [];
            }

            // Unwrap NonNull and ListOf to get to the InputObjectType
            $type = self::unwrapType($type);

            if (!$type instanceof InputObjectType) {
                return [];
            }

            // Get the fields of this InputObjectType for the next iteration
            $currentFields = $type->getFields();
        }

        // At this point, $currentFields contains the fields at the sibling level
        return array_keys($currentFields);
    }

    /**
     * Extract a GraphQL type from a field definition, which may be an array or an InputObjectField.
     *
     * @param InputObjectField|array<string,mixed>|object $fieldDef
     */
    protected static function extractType($fieldDef): ?GraphqlType
    {
        if ($fieldDef instanceof InputObjectField) {
            return $fieldDef->getType();
        }

        if (\is_array($fieldDef) && isset($fieldDef['type'])) {
            $type = $fieldDef['type'];

            if ($type instanceof GraphqlType) {
                return $type;
            }
        }

        if (\is_object($fieldDef) && property_exists($fieldDef, 'type')) {
            $type = $fieldDef->type;

            if ($type instanceof GraphqlType) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Unwrap NonNull and ListOfType wrappers to get to the underlying named type.
     */
    protected static function unwrapType(GraphqlType $type): GraphqlType
    {
        while ($type instanceof NonNull || $type instanceof ListOfType) {
            $type = $type->getWrappedType();
        }

        return $type;
    }
}
