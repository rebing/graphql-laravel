<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ArgsVariants;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Support\Type as BaseType;
use Rebing\GraphQL\Tests\TestCase;

// NOTE: PHP class aliases are case-insensitive — never pair aliases that
// differ only by casing (e.g. GraphqlType vs GraphQLType): it is a fatal.

class PrivacyConfiguredDefaultResolverTest extends TestCase
{
    public static function upperResolver(mixed $root, array $args, mixed $context, ResolveInfo $info): mixed
    {
        $value = \GraphQL\Executor\Executor::defaultFieldResolver($root, $args, $context, $info);

        return \is_string($value) ? strtoupper($value) : $value;
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.defaultFieldResolver', [self::class, 'upperResolver']);
        $app['config']->set('graphql.schemas.default', [
            'query' => [
                PrivacyDefaultResolverQuery::class,
            ],
        ]);
        $app['config']->set('graphql.types', [
            PrivacyDefaultResolverType::class,
        ]);
    }

    public function testPrivacyAllowedFieldUsesConfiguredDefaultResolver(): void
    {
        $result = $this->httpGraphql('{ privacyDefaultResolver { open } }');

        self::assertSame('VISIBLE', $result['data']['privacyDefaultResolver']['open']);
    }

    public function testPrivacyDeniedFieldStillReturnsNull(): void
    {
        $result = $this->httpGraphql('{ privacyDefaultResolver { closed } }');

        self::assertNull($result['data']['privacyDefaultResolver']['closed']);
    }
}

class PrivacyDefaultResolverType extends BaseType
{
    /** @var array<string,string> */
    protected $attributes = [
        'name' => 'PrivacyDefaultResolver',
    ];

    /** @return array<string,mixed> */
    public function fields(): array
    {
        return [
            'open' => [
                'type' => Type::string(),
                'privacy' => static fn (): bool => true,
            ],
            'closed' => [
                'type' => Type::string(),
                'privacy' => static fn (): bool => false,
            ],
        ];
    }
}

class PrivacyDefaultResolverQuery extends Query
{
    /** @var array<string,string> */
    protected $attributes = [
        'name' => 'privacyDefaultResolver',
    ];

    public function type(): Type
    {
        return \Rebing\GraphQL\Support\Facades\GraphQL::type('PrivacyDefaultResolver');
    }

    /**
     * @param mixed $root
     * @param array<string,mixed> $args
     * @return array<string,string>
     */
    public function resolve($root, array $args): array
    {
        return ['open' => 'visible', 'closed' => 'hidden'];
    }
}
