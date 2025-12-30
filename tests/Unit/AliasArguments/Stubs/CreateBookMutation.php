<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\AliasArguments\Stubs;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

/**
 * Mutation using circular input types (Book <-> Author).
 * Used to test exponential time complexity fix in AliasArguments.
 */
class CreateBookMutation extends Mutation
{
    /** @var array<string,string> */
    protected $attributes = [
        'name' => 'createBook',
    ];

    public function type(): Type
    {
        return GraphQL::type(ExampleType::TYPE);
    }

    public function args(): array
    {
        return [
            'book' => [
                'type' => GraphQL::type(BookInputObject::TYPE),
            ],
        ];
    }

    /**
     * @param mixed $root
     * @param array<string,mixed> $args
     * @return array<string,mixed>
     */
    public function resolve($root, array $args): array
    {
        return [
            'test' => \Safe\json_encode($args),
        ];
    }
}
