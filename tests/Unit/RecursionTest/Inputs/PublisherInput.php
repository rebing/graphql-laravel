<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\RecursionTest\Inputs;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InputType;

class PublisherInput extends InputType
{
    /** @var array<string,string> */
    protected $attributes = [
        'name' => 'PublisherInput',
        'description' => 'An example input',
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
            'authors' => [
                'type' => Type::listOf(GraphQL::type('AuthorInput')),
            ],
            'bestSellingAuthor' => [
                'type' => GraphQL::type('AuthorInput'),
            ],
        ];
    }
}
