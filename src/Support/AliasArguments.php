<?php

namespace Rebing\GraphQL\Support;

use Crissi\ArrayKeyChange\ArrayKeyChange;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\WrappingType;

class AliasArguments
{
    private $typedArgs;
    private $arguments;

    public function __construct(array $typedArgs, array $arguments)
    {
        $this->typedArgs = $typedArgs;
        $this->arguments = $arguments;
    }

    public function get(): array
    {
        $pathsWithAlias = $this->getAliasesInFields($this->typedArgs, '');

        return ArrayKeyChange::in($this->arguments)
            ->skipMissingPaths()
            ->modify($pathsWithAlias);
    }

    public function getAliasesInFields(array $fields, $prefix = '', $parentType = null): array
    {
        $pathAndAlias = [];
        foreach ($fields as $name => $arg) {
            $arg = (object) $arg;
            $type = $arg->type ?? null;

            $newPrefix = empty($prefix) ? $name : $prefix.'.'.$name;

            if (isset($arg->alias)) {
                $pathAndAlias[$newPrefix] = $arg->alias;
            }

            if ($this->isWrappedInList($type)) {
                $newPrefix .= '.*';
            }

            $type = $this->getWrappedType($type);

            if ($type instanceof InputObjectType) {
                if ($parentType) {
                    // in case the field is a self reference we must not do
                    // a recursive call as it will never stop
                    if ($type->toString() == $parentType->toString()) {
                        continue;
                    }
                }

                $pathAndAlias = $pathAndAlias + $this->getAliasesInFields($type->getFields(), $newPrefix, $type);
            }
        }

        return $pathAndAlias;
    }

    private function isWrappedInList($type): bool
    {
        if ($type instanceof NonNull) {
            $type = $type->getWrappedType();
        }

        return $type instanceof ListOfType;
    }

    /**
     * @param mixed $type
     * @return mixed
     */
    private function getWrappedType($type)
    {
        if ($type instanceof WrappingType) {
            $type = $type->getWrappedType(true);
        }

        return $type;
    }
}
