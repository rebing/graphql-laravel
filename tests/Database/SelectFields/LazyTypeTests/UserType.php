<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\LazyTypeTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;
use Rebing\GraphQL\Tests\Support\Models\User;

class UserType extends GraphQLType
{
    protected $attributes = [
        'name' => 'User',
        'model' => User::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::id()),
            ],
            'name' => [
                'type' => Type::nonNull(Type::string()),
            ],
            // Circular reference: User -> posts -> Post -> user -> User
            // Uses a lazy thunk to break the cycle (standard webonyx pattern).
            // This causes FieldDefinition::$config['type'] to be a Closure,
            // which must be resolved via ->getType() before use.
            'posts' => [
                'type' => fn () => Type::nonNull(Type::listOf(Type::nonNull(GraphQL::type('Post')))),
            ],
        ];
    }
}
