<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Support;

use GraphQL\Type\Definition\Type as GraphqlType;
use GraphQL\Type\Definition\EnumType as GraphqlEnumType;

abstract class EnumType extends Type
{
    public function toType(): GraphqlType
    {
        return new GraphqlEnumType($this->toArray());
    }
}
