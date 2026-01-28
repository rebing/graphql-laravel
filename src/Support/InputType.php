<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type as GraphqlType;

abstract class InputType extends Type
{
    /**
     * Indicates if this is a OneOf Input Object.
     * OneOf Input Objects require exactly one field to be provided.
     *
     * Can be overridden in subclasses or set via $attributes['isOneOf'].
     *
     * @return bool
     */
    protected function isOneOf(): bool
    {
        return false;
    }

    public function toType(): GraphqlType
    {
        $config = $this->toArray();

        // Check if isOneOf is specified in attributes or via method
        $attributes = $this->getAttributes();
        $isOneOf = $attributes['isOneOf'] ?? $this->isOneOf();

        if ($isOneOf) {
            $config['isOneOf'] = true;
        }

        return new InputObjectType($config);
    }
}
