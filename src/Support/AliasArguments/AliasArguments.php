<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Support\AliasArguments;

use GraphQL\Type\Definition\InputObjectField;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\WrappingType;

class AliasArguments
{
    public function get(array $typedArgs, array $arguments): array
    {
        $pathsWithAlias = $this->getAliasesInFields($typedArgs, '');

        return (new ArrayKeyChange())->modify($arguments, $pathsWithAlias);
    }

    private function getAliasesInFields(array $fields, $prefix = '', $parentType = null): array
    {
        $pathAndAlias = [];
        foreach ($fields as $name => $arg) {
            // $arg is either an array DSL notation or an InputObjectField
            $arg = $arg instanceof InputObjectField ? $arg : (object) $arg;

            $type = $arg->type ?? null;

            if (null === $type) {
                continue;
            }

            $newPrefix = $prefix ? $prefix.'.'.$name : $name;

            if (isset($arg->alias)) {
                $pathAndAlias[$newPrefix] = $arg->alias;
            }

            if ($this->isWrappedInList($type)) {
                $newPrefix .= '.*';
            }

            $type = $this->getWrappedType($type);

            if (! ($type instanceof InputObjectType)) {
                continue;
            }

            if ($parentType && $type->toString() === $parentType->toString()) {
                // in case the field is a self reference we must not do
                // a recursive call as it will never stop
                continue;
            }

            $pathAndAlias = $pathAndAlias + $this->getAliasesInFields($type->getFields(), $newPrefix, $type);
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
            $type = $type->getWrappedType(true);
        }

        return $type;
    }
}
