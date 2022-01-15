<?php

namespace Rebing\GraphQL\Tests\Support\Types\Wrappers;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class PaginationMeta
{
    public static function type(string $typeName): Type
    {
        return GraphQL::wrapType(
            $typeName,
            'PaginationMeta',
            PaginationMetaType::class
        );
    }
}
