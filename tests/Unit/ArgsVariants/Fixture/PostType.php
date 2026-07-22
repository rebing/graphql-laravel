<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ArgsVariants\Fixture;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class PostType extends GraphQLType
{
    /** @var array<string,string> */
    protected $attributes = [
        'name' => 'VariantPost',
    ];

    /**
     * @return array<string,mixed>
     */
    public function fields(): array
    {
        return [
            'id' => ['type' => Type::nonNull(Type::int())],
            'title' => ['type' => Type::string()],
            'comments' => [
                'type' => Type::listOf(GraphQL::type('VariantComment')),
                'args' => [
                    'top' => ['type' => Type::int()],
                    'year' => ['type' => Type::int()],
                ],
            ],
            'author' => [
                'type' => GraphQL::type('VariantAuthor'),
            ],
        ];
    }
}
