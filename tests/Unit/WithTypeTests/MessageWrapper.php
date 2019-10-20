<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\WithTypeTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

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
