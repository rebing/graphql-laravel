<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\ValidateFieldTests;

use GraphQL\Type\Definition\Type;
use PHPUnit\Framework\Assert;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;
use Rebing\GraphQL\Tests\Support\Models\Post;

class PostType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Post',
        'model' => Post::class,
    ];

    public function fields(): array
    {
        return [
            'body_selectable_false' => [
                'alias' => 'body',
                'type' => Type::string(),
                'selectable' => false,
            ],
            'body_selectable_true' => [
                'alias' => 'body',
                'type' => Type::string(),
                'selectable' => true,
            ],
            'body_selectable_null' => [
                'alias' => 'body',
                'type' => Type::string(),
                'selectable' => null,
            ],
            'comments' => [
                'type' => Type::nonNull(Type::listOf(Type::nonNull(GraphQL::type('Comment')))),
            ],
            'id' => [
                'type' => Type::nonNull(Type::ID()),
            ],
            'title' => [
                'type' => Type::nonNull(Type::string()),
            ],
            'title_privacy_closure_allowed' => [
                'alias' => 'title',
                'type' => Type::string(),
                'privacy' => function (array $args): bool {
                    return true;
                },
            ],
            'title_privacy_closure_denied' => [
                'alias' => 'title',
                'type' => Type::string(),
                'privacy' => function (array $args): bool {
                    return false;
                },
            ],
            'title_privacy_class_allowed' => [
                'alias' => 'title',
                'type' => Type::string(),
                'privacy' => PrivacyAllowed::class,
            ],
            'title_privacy_class_denied' => [
                'alias' => 'title',
                'type' => Type::string(),
                'privacy' => PrivacyDenied::class,
            ],
            'title_privacy_class_allowed_called_twice' => [
                'alias' => 'title',
                'type' => Type::string(),
                'privacy' => PrivacyAllowed::class,
            ],
            'title_privacy_closure_args' => [
                'alias' => 'title',
                'type' => Type::string(),
                'args' => [
                    'arg_from_field' => [
                        'type' => Type::boolean(),
                    ],
                ],
                'privacy' => function (array $queryArgs): bool {
                    $expectedQueryArgs = [
                        'arg_from_query' => true,
                    ];
                    Assert::assertSame($expectedQueryArgs, $queryArgs);

                    return true;
                },
            ],
            'title_privacy_class_args' => [
                'alias' => 'title',
                'type' => Type::string(),
                'args' => [
                    'arg_from_field' => [
                        'type' => Type::boolean(),
                    ],
                ],
                'privacy' => PrivacyArgs::class,
            ],
            'title_privacy_wrong_type' => [
                'type' => Type::string(),
                'privacy' => true,
            ],
        ];
    }
}
