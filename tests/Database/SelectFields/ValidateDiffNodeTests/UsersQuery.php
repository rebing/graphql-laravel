<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\ValidateDiffNodeTests;

use PHPUnit\Framework\Assert;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Query;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\SelectFields;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Tests\Support\Models\User;

class UsersQuery extends Query
{
    protected $attributes = [
        'name' => 'users',
    ];

    public function args(): array
    {
        return [
            'id' => [
                'type' => Type::int(),
            ],
            'name' => [
                'type' => Type::string(),
            ],
            'price' => [
                'type' => Type::float(),
            ],
            'status' => [
                'type' => Type::boolean(),
            ],
            'flag' => [
                'type' => Type::string(),
            ],
            'author' => [
                'type' => GraphQL::type('Episode'),
            ],
            'post' => [
                'type' => GraphQL::type('Filter'),
            ],
            'keywords' => [
                'type' => Type::listOf(Type::string()),
            ],
            'customType' => [
                'type' => GraphQL::type('MyCustomScalarString'),
            ],
        ];
    }

    public function type(): Type
    {
        return Type::nonNull(Type::listOf(Type::nonNull(GraphQL::type('User'))));
    }

    public function resolve($root, $args, $context, ResolveInfo $resolveInfo, \Closure $getSelectFields)
    {
        /** @var SelectFields $fields */
        $fields = $getSelectFields();
        $expectedQueryArgs = [
            'id' => 1,
            'name' => 'john',
            'price' => 1.2,
            'status' => true,
            'flag' => null,
            'author' => 'NEWHOPE',
            'post' => [
                'body' => 'body',
                'id' => 1,
            ],
            'keywords' => [
                'key1',
                'key2',
                'key3',
            ],
            'customType' => 'hello world',
        ];
        Assert::assertSame($expectedQueryArgs, $args);

        return User::select($fields->getSelect())
            ->with($fields->getRelations())
            ->get();
    }
}
