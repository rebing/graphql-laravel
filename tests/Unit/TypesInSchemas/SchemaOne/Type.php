<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\TypesInSchemas\SchemaOne;

use GraphQL\Type\Definition\Type as BaseType;
use Rebing\GraphQL\Support\Type as GraphQLType;

class Type extends GraphQLType
{
    protected $attributes = [
        'name' => 'Type',
    ];

    public function fields(): array
    {
        return [
            'name' => [
                'type' => BaseType::nonNull(BaseType::string()),
            ],
        ];
    }
}
