<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\NestedTypePrivacyTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class ChildType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Child',
    ];

    public function fields(): array
    {
        return [
            'public_name' => [
                'type' => Type::string(),
            ],
            'secret_name' => [
                'type' => Type::string(),
                'privacy' => function (mixed $root, array $args): bool {
                    return false;
                },
            ],
            'allowed_name' => [
                'type' => Type::string(),
                'privacy' => function (mixed $root, array $args): bool {
                    return true;
                },
            ],
        ];
    }
}
