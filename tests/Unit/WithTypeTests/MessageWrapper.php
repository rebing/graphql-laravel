<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\WithTypeTests;

use Rebing\GraphQL\Support\Facades\GraphQL;
use GraphQL\Type\Definition\Type;

class MessageWrapper
{
    /**
     * @param string $typeName type graphql
     * @return Type
     */
    public static function type(string $typeName): Type
    {
        return GraphQL::wrapType(
            $typeName,
            $typeName.'Messages',
            WrapperType::class
        );
    }
}
