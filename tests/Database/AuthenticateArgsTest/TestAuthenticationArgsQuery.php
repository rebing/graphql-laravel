<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\AuthenticateArgsTest;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use PHPUnit\Framework\Assert;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Support\SelectFields;
use Rebing\GraphQL\Tests\Database\AuthorizeArgsTests\GraphQLContext;

class TestAuthenticationArgsQuery extends Query
{
    protected $attributes = [
        'name' => 'testAuthenticationArgs',
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::string());
    }

    public function args(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::ID()),
            ],
        ];
    }

    public function authenticate(
        $root,
        array $args,
        $ctx,
        ResolveInfo $resolveInfo = null,
        Closure $getSelectFields = null
    ): bool {
        Assert::assertNull($root);

        $expectedArgs = [
            'id' => 'foobar',
        ];
        Assert::assertSame($expectedArgs, $args);

        Assert::assertInstanceOf(GraphQLContext::class, $ctx);

        Assert::assertInstanceOf(ResolveInfo::class, $resolveInfo);

        $selectFields = $getSelectFields();
        Assert::assertInstanceOf(SelectFields::class, $selectFields);

        return true;
    }

    public function resolve(): void
    {
    }
}
