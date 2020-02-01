<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\RecursionTest\Inputs;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InputType;

class BookInput extends InputType
{
    /**
     * @var array<string,string>
     */
    protected $attributes = [
        'name' => 'BookInput',
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
            'publisher' => [
                'type' => GraphQL::type('PublisherInput'),
            ],
            'author' => [
                'type' => GraphQL::type('AuthorInput'),
            ],
        ];
    }
}
