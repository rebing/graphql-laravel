<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support\Objects;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Field;

class ExampleField extends Field
{
    protected $attributes = [
        'name' => 'example',
    ];

    public function type(): Type
    {
        return Type::listOf(Type::string());
    }

    public function args(): array
    {
        return [
            'index' => [
                'name' => 'index',
                'type' => Type::int(),
            ],
        ];
    }

    /** @return list<string> */
    public function resolve(mixed $root, array $args): array
    {
        return ['test'];
    }
}
