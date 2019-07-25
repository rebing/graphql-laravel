<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\InterfaceTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;
use Rebing\GraphQL\Tests\Support\Models\Comment;

class ExampleRelationType extends GraphQLType
{
    protected $attributes = [
        'name' => 'ExampleRelation',
        'model' => Comment::class,
    ];

    public function fields(): array
    {
        return [
            'title' => [
                'type' => Type::nonNull(Type::string()),
            ],
        ];
    }
}
