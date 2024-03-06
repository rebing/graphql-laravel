<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\PrimaryKeyTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InterfaceType;
use Rebing\GraphQL\Support\Type as GraphQLType;
use Rebing\GraphQL\Tests\Support\Models\Post;

class ModelInterfaceType extends InterfaceType
{
    protected $attributes = [
        'name' => 'ModelInterface',
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::ID()),
            ],
        ];
    }

    public function resolveType($root)
    {
        return GraphQL::type('Post');
    }
}
