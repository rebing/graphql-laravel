<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\ValidateDiffNodeTests;

use GraphQL\Type\Definition\Type;
use PHPUnit\Framework\Assert;
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
                'type' => Type::int(),
            ],
            'name' => [
                'type' => Type::string(),
            ],
            'posts' => [
                'type' => Type::listOf(Type::nonNull(GraphQL::type('Post'))),
                'args' => [
                    'id' => [
                        'type' => Type::int(),
                    ],
                    'name' => [
                        'type' => Type::string(),
                    ],
                    'price' => [
                        'type' => Type::float(),
                    ],
                    'status' => [
                        'type' => Type::boolean(),
                    ],
                    'flag' => [
                        'type' => Type::string(),
                    ],
                    'author' => [
                        'type' => GraphQL::type('Episode'),
                    ],
                    'post' => [
                        'type' => GraphQL::type('Filter'),
                    ],
                    'keywords' => [
                        'type' => Type::listOf(Type::string()),
                    ],
                    'customType' => [
                        'type' => GraphQL::type('MyCustomScalarString'),
                    ],
                ],
                'query' => function (array $args, $query) {
                    $expectedQueryArgs = [
                        'id' => 2,
                        'name' => 'tom',
                        'price' => 1.3,
                        'status' => false,
                        'flag' => null,
                        'author' => 'EMPIRE',
                        'post' => [
                            'id' => 2,
                            'body' => 'body2',
                        ],
                        'keywords' => [
                            'key4',
                            'key5',
                            'key6',
                        ],
                        'customType' => 'custom string',
                    ];
                    Assert::assertSame($expectedQueryArgs, $args);

                    return $query;
                },
            ],
        ];
    }
}
