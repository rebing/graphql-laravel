<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\AuthorizeArgsTests;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use PHPUnit\Framework\Assert;
use Rebing\GraphQL\Support\Query;

class TestAuthorizationArgsQuery extends Query
{
    protected $attributes = [
        'name' => 'testAuthorizationArgs',
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

    /**
     * @param array<string,mixed> $args
     */
    public function authorize(
        $root,
        array $args,
        $ctx,
        ?ResolveInfo $resolveInfo = null,
    ): bool {
        Assert::assertNull($root);

        $expectedArgs = [
            'id' => 'foobar',
        ];
        Assert::assertSame($expectedArgs, $args);

        Assert::assertInstanceOf(GraphQLContext::class, $ctx);

        Assert::assertInstanceOf(ResolveInfo::class, $resolveInfo);

        return true;
    }

    public function resolve(): void
    {
    }
}
