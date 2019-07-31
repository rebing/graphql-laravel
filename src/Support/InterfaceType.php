<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Support;

use Closure;
use GraphQL\Type\Definition\Type as GraphqlType;
use GraphQL\Type\Definition\InterfaceType as BaseInterfaceType;

abstract class InterfaceType extends Type
{
    protected function getTypeResolver(): ?Closure
    {
        if (! method_exists($this, 'resolveType')) {
            return null;
        }

        $resolver = [$this, 'resolveType'];

        return function () use ($resolver) {
            $args = func_get_args();

            return call_user_func_array($resolver, $args);
        };
    }

    /**
     * Get the attributes from the container.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        $attributes = parent::getAttributes();

        $resolver = $this->getTypeResolver();
        if ($resolver) {
            $attributes['resolveType'] = $resolver;
        }

        return $attributes;
    }

    public function toType(): GraphqlType
    {
        return new BaseInterfaceType($this->toArray());
    }
}
