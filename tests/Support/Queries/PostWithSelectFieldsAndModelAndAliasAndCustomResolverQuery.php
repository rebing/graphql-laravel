<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Support\Queries;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Support\SelectFields;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Tests\Support\Models\Post;

class PostWithSelectFieldsAndModelAndAliasAndCustomResolverQuery extends Query
{
    protected $attributes = [
        'name' => 'postWithSelectFieldsAndModelAndAliasAndCustomResolver',
    ];

    public function type()
    {
        return GraphQL::type('PostWithModelAndAliasAndCustomResolver');
    }

    public function args()
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::id()),
            ],
        ];
    }

    public function resolve($root, $args, SelectFields $selectFields)
    {
        return Post
            ::select($selectFields->getSelect())
            ->findOrFail($args['id']);
    }
}
