<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\RecursionTest\Inputs;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InputType;

class AuthorInput extends InputType
{
    /**
     * @var array<string,string>
     */
    protected $attributes = [
        'name' => 'AuthorInput',
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
            'books' => [
                'type' => Type::listOf(GraphQL::type('BookInput')),
            ],
            'bestSellingBook' => [
                'type' => GraphQL::type('BookInput'),
            ],
        ];
    }
}
