<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\RecursionTest\Mutations;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class SaveAuthor extends Mutation
{
    /** @var array<string,string> */
    protected $attributes = [
        'name' => 'SaveAuthor',
    ];

    public function type(): Type
    {
        return Type::boolean();
    }

    /**
     * @return array<string,mixed>
     */
    public function args(): array
    {
        return [
            'author' => [
                'name' => 'author',
                'type' => GraphQL::type('AuthorInput'),
            ],
        ];
    }

    /**
     * @param array<string,mixed> $args
     * @return array<string,mixed>
     */
    protected function rules(array $args = []): array
    {
        return [
            'author' => ['required'],
        ];
    }

    /**
     * @param mixed $root
     * @param array<string,mixed> $args
     */
    public function resolve($root, $args): bool
    {
        return true;
    }
}
