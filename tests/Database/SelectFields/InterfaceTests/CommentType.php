<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\InterfaceTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;
use Rebing\GraphQL\Tests\Support\Models\Comment;

class CommentType extends GraphQLType
{
    protected $attributes = [
        'name'  => 'Comment',
        'model' => Comment::class,
    ];

    public function fields(): array
    {
        $interface = GraphQL::type('LikableInterface');

        return [
                'title' => [
                    'type'  => Type::nonNull(Type::string()),
                    'alias' => 'title',
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
