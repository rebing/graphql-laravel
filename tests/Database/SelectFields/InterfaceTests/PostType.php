<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\InterfaceTests;

use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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
        $interface = GraphQL::type('LikableInterface');

        return
            [
                'created_at' => [
                    'type' => Type::string(),
                ],
                'alias_updated_at' => [
                    'type'  => Type::string(),
                    'alias' => 'updated_at',
                ],
                'likes' => [
                    'type' => Type::listOf(GraphQL::type('Like')),
                    'query' => function (array $args, MorphMany $query) {
                        return $query->whereRaw('0=0');
                    },
                ],
            ] + $interface->getFields();
    }

    public function interfaces(): array
    {
        return [
            GraphQL::type('LikableInterface'),
        ];
    }
}
