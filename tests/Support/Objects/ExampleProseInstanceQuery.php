<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support\Objects;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Query;

class ExampleProseInstanceQuery extends Query
{
    public function __construct(public string $prose)
    {
    }

    protected $attributes = [
        'name' => 'exampleProse',
    ];

    public function type(): Type
    {
        return Type::string();
    }

    public function args(): array
    {
        return [];
    }

    /**
     * @param mixed $root
     * @param array<string,mixed> $args
     */
    public function resolve($root, $args): string
    {
        return $this->prose;
    }
}
