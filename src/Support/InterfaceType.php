<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support;

use Closure;
use GraphQL\Type\Definition\InterfaceType as BaseInterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphqlType;

abstract class InterfaceType extends Type
{
    protected function getTypeResolver(): ?Closure
    {
        if (!method_exists($this, 'resolveType')) {
            return null;
        }

        return $this->resolveType(...);
    }

    /**
     * @return Closure(): list<ObjectType>|null
     */
    protected function getTypesResolver(): ?Closure
    {
        if (!method_exists($this, 'types')) {
            return null;
        }

        return $this->types(...);
    }

    /**
     * Get the attributes from the container.
     */
    public function getAttributes(): array
    {
        $attributes = parent::getAttributes();

        $resolverType = $this->getTypeResolver();

        if ($resolverType) {
            $attributes['resolveType'] = $resolverType;
        }

        $resolverTypes = $this->getTypesResolver();

        if ($resolverTypes) {
            $attributes['types'] = $resolverTypes;
        }

        return $attributes;
    }

    public function toType(): GraphqlType
    {
        return new BaseInterfaceType($this->toArray());
    }
}
