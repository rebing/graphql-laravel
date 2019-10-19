<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\WithTypeTests;

use GraphQL;

class MessageWrapper
{
    /**
     * @param string $typeName type graphql
     * @return void
     */
    public static function type(string $typeName)
    {
        return GraphQL::wrapType(
            $typeName,
            $typeName.'Messages',
            WrapperType::class
        );
    }
}
