<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\ValidationOfFieldArguments;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class TestQuery extends Query
{
    /**
     * @var array<string,string>
     */
    protected $attributes = [
        'name' => 'test',
    ];

    public function type(): Type
    {
        return GraphQL::type('AccountType');
    }

    public function args(): array
    {
        return [
            'id' => [
                'type' => Type::int(),
                'rules' => ['integer', 'max:2'],
            ],
        ];
    }

    /**
     * @param mixed $root
     * @param array<string,mixed> $args
     * @param mixed $context
     * @return array<string,mixed>
     */
    public function resolve($root, array $args, $context): array
    {
        return [
            'name' => 'fff',
            'profile' => null,
        ];
    }
}
