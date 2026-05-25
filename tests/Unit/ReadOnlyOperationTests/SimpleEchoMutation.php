<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ReadOnlyOperationTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Mutation;

/**
 * Minimal mutation fixture for `ReadOnlyOperation*Test`.
 *
 * Kept separate from the shared `UpdateExampleMutation` because that one
 * carries additional required validation rules that aren't relevant here
 * and would cause unrelated failures in the GET-mutation test paths.
 */
class SimpleEchoMutation extends Mutation
{
    /** @var array<string,string> */
    protected $attributes = [
        'name' => 'simpleEcho',
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::string());
    }

    public function args(): array
    {
        return [
            'value' => [
                'type' => Type::nonNull(Type::string()),
            ],
        ];
    }

    /**
     * @param mixed $root
     * @param array<string,string> $args
     * @param mixed $context
     */
    public function resolve($root, array $args, $context): string
    {
        return $args['value'];
    }
}
