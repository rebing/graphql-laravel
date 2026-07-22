<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ArgsVariants\Fixture;

use GraphQL\Type\Definition\Type as GraphqlType;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class RulesCaptureQuery extends Query
{
    /** @var array<string,string> */
    protected $attributes = [
        'name' => 'rulesCapture',
    ];

    public function type(): GraphqlType
    {
        return Type::listOf(GraphQL::type('RulesPost'));
    }

    /**
     * @param mixed $root
     * @param array<string,mixed> $args
     * @return array<int,mixed>
     */
    public function resolve($root, array $args): array
    {
        return [];
    }
}
