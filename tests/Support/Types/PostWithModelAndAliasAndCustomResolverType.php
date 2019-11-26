<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Support\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;
use Rebing\GraphQL\Tests\Support\Models\Post;

class PostWithModelAndAliasAndCustomResolverType extends GraphQLType
{
    protected $attributes = [
        'name' => 'PostWithModelAndAliasAndCustomResolver',
        'model' => Post::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::id()),
            ],
            'description' => [
                'type' => Type::nonNull(Type::string()),
                'alias' => 'title',
                'resolve' => function (): string {
                    return 'Custom resolver';
                },
            ],
        ];
    }
}
