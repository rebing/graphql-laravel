<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ArgsVariants\Fixture;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class AuthorType extends GraphQLType
{
    /** @var array<string,string> */
    protected $attributes = [
        'name' => 'VariantAuthor',
    ];

    /**
     * @return array<string,mixed>
     */
    public function fields(): array
    {
        return [
            'id' => ['type' => Type::nonNull(Type::int())],
            'comments' => [
                'type' => Type::listOf(GraphQL::type('VariantComment')),
                'args' => [
                    'top' => ['type' => Type::int()],
                ],
            ],
        ];
    }
}
