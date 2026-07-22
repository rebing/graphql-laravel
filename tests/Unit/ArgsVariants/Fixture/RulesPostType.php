<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ArgsVariants\Fixture;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class RulesPostType extends GraphQLType
{
    /** @var array<string,string> */
    protected $attributes = [
        'name' => 'RulesPost',
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
                    'top' => [
                        'type' => Type::int(),
                        'rules' => ['integer', 'max:10', 'not_in:7'],
                    ],
                ],
            ],
        ];
    }
}
