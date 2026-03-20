<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support\Objects;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Field;

class ExampleValidationField extends Field
{
    protected $attributes = [
        'name' => 'example_validation',
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
                'rules' => ['required'],
            ],
        ];
    }

    /**
     * @param array<string,mixed> $args
     * @return list<string>
     */
    public function resolve(mixed $root, array $args): array
    {
        return ['test'];
    }
}
