<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\AliasArguments;

use GraphQL\Type\Definition\InputObjectField;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\WrappingType;

class AliasArguments
{
    /** @var array<string,mixed> */
    private $queryArguments;
    /** @var array<string,mixed> */
    private $requestArguments;
    /** @var int */
    private $maxDepth;

    /**
     * @param array<string,mixed> $queryArguments
     * @param array<string,mixed> $requestArguments
     */
    public function __construct(array $queryArguments, array $requestArguments)
    {
        $this->queryArguments = $queryArguments;
        $this->requestArguments = $requestArguments;
        $this->maxDepth = $this->getArrayDepth($this->requestArguments);
    }

    public function get(): array
    {
        $pathsWithAlias = $this->getAliasesInFields($this->queryArguments, $this->requestArguments);

        return (new ArrayKeyChange())->modify($this->requestArguments, $pathsWithAlias);
    }

    /**
     * c/p from https://stackoverflow.com/questions/262891/is-there-a-way-to-find-out-how-deep-a-php-array-is/262944#262944.
     *
     * @param array<string,mixed> $array
     */
    protected function getArrayDepth(array $array): int
    {
        $maxDepth = 1;

        foreach ($array as $value) {
            if (\is_array($value)) {
                $depth = $this->getArrayDepth($value) + 1;

                if ($depth > $maxDepth) {
                    $maxDepth = $depth;
                }
            }
        }

        return $maxDepth;
    }

    /**
     * Get aliases from fields, only traversing fields present in request data.
     *
     * This prevents exponential time complexity with circular type references by only
     * exploring the actual data structure sent by the client, not all possible fields
     * in the type schema.
     *
     * @param array<string,mixed> $fields Type field definitions
     * @param array<string,mixed>|null $requestData Actual request data at this level (null for initial call)
     * @param string $prefix Path prefix for nested fields
     *
     * @return array<string,string> Map of field paths to their aliases
     */
    protected function getAliasesInFields(array $fields, ?array $requestData = null, string $prefix = ''): array
    {
        // checks for traversal beyond the max depth
        // this scenario occurs in types with recursive relations
        if (substr_count($prefix, '.') > $this->maxDepth) {
            return [];
        }
        $pathAndAlias = [];

        foreach ($fields as $name => $arg) {
            // KEY FIX: Skip fields not present in actual request data
            // This prevents exponential explosion with circular type references
            if (null !== $requestData && !\array_key_exists($name, $requestData)) {
                continue;
            }

            $type = null;

            // $arg is either an array DSL notation or an InputObjectField
            if ($arg instanceof InputObjectField) {
                $type = $arg->getType();
            } else {
                $arg = (object) $arg;
                $type = $arg->type ?? null;
            }

            if (null === $type) {
                continue;
            }

            $newPrefix = $prefix ? $prefix . '.' . $name : $name;

            $alias = $arg->config['alias'] ?? $arg->alias ?? null;

            if ($alias) {
                $pathAndAlias[$newPrefix] = $alias;
            }

            $isWrappedInList = $this->isWrappedInList($type);

            if ($isWrappedInList) {
                $newPrefix .= '.*';
            }

            $type = $this->getWrappedType($type);

            if (!($type instanceof InputObjectType)) {
                continue;
            }

            // Get the actual data at this field (if requestData provided)
            $fieldData = null !== $requestData ? ($requestData[$name] ?? null) : null;

            // If it's a list, process each item
            if ($isWrappedInList && \is_array($fieldData)) {
                foreach ($fieldData as $item) {
                    if (\is_array($item)) {
                        $pathAndAlias = $pathAndAlias + $this->getAliasesInFields(
                            $type->getFields(),
                            $item,
                            $newPrefix,
                        );
                    }
                }
            } elseif (null !== $fieldData && \is_array($fieldData)) {
                // Single object
                $pathAndAlias = $pathAndAlias + $this->getAliasesInFields(
                    $type->getFields(),
                    $fieldData,
                    $newPrefix,
                );
            }
        }

        return $pathAndAlias;
    }

    private function isWrappedInList(Type $type): bool
    {
        if ($type instanceof NonNull) {
            $type = $type->getWrappedType();
        }

        return $type instanceof ListOfType;
    }

    private function getWrappedType(Type $type): Type
    {
        if ($type instanceof WrappingType) {
            $type = $type->getInnermostType();
        }

        return $type;
    }
}
