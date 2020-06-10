<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Support\Objects;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Field;

class ExampleFieldSelectFieldsResolve extends Field
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

    public function resolve($root, $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields): array
    {
        return [$getSelectFields()];
    }
}
