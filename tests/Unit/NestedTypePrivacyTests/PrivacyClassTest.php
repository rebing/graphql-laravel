<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\NestedTypePrivacyTests;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;
use Rebing\GraphQL\Tests\TestCase;
use RuntimeException;

/**
 * Exercises the Privacy class (`Rebing\GraphQL\Support\Privacy`) as documented in
 * the README under "Privacy / Using a Privacy class". Existing privacy tests use
 * closures, leaving the class-string code path uncovered.
 *
 * Also covers the privacy-related branches in `Rebing\GraphQL\Support\Type`:
 *   - `evaluatePrivacy()` with a class-string privacy
 *   - `evaluatePrivacy()` with an unsupported privacy value (RuntimeException)
 *   - `wrapResolverWithPrivacy()` when an explicit resolver is provided
 */
class PrivacyClassTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('graphql.schemas.default', [
            'query' => [
                PrivacyClassParentQuery::class,
            ],
        ]);

        $app['config']->set('graphql.types', [
            PrivacyClassParentType::class,
            PrivacyClassChildType::class,
        ]);

        AllowPrivacy::$calls = [];
        DenyPrivacy::$calls = 0;
    }

    public function testAllowPrivacyClassReturnsField(): void
    {
        $query = <<<'GRAPHQL'
{
  parentWithClassPrivacy {
    name
    child {
      public_name
      allowed_via_class
    }
  }
}
GRAPHQL;

        $result = $this->httpGraphql($query);

        self::assertSame([
            'data' => [
                'parentWithClassPrivacy' => [
                    'name' => 'parent name',
                    'child' => [
                        'public_name' => 'public value',
                        'allowed_via_class' => 'allowed value',
                    ],
                ],
            ],
        ], $result);

        // The class-string path goes through Privacy::fire() -> validate()
        self::assertCount(1, AllowPrivacy::$calls);
        self::assertSame('allowed value', AllowPrivacy::$calls[0]['root']->allowed_via_class);
        self::assertSame([], AllowPrivacy::$calls[0]['args']);
        self::assertTrue(AllowPrivacy::$calls[0]['hasResolveInfo']);
    }

    public function testDenyPrivacyClassReturnsNull(): void
    {
        $query = <<<'GRAPHQL'
{
  parentWithClassPrivacy {
    child {
      denied_via_class
    }
  }
}
GRAPHQL;

        $result = $this->httpGraphql($query);

        self::assertSame([
            'data' => [
                'parentWithClassPrivacy' => [
                    'child' => [
                        'denied_via_class' => null,
                    ],
                ],
            ],
        ], $result);

        self::assertSame(1, DenyPrivacy::$calls);
    }

    public function testPrivacyClassWithExplicitResolver(): void
    {
        // Covers `wrapResolverWithPrivacy` line 132: an explicit `resolve` is
        // provided alongside `privacy`, so when privacy passes the original
        // resolver runs (instead of the default field resolver).
        $query = <<<'GRAPHQL'
{
  parentWithClassPrivacy {
    child {
      allowed_with_resolver
    }
  }
}
GRAPHQL;

        $result = $this->httpGraphql($query);

        self::assertSame([
            'data' => [
                'parentWithClassPrivacy' => [
                    'child' => [
                        'allowed_with_resolver' => 'resolved-allowed',
                    ],
                ],
            ],
        ], $result);

        self::assertCount(1, AllowPrivacy::$calls);
    }

    public function testDeniedPrivacyShortCircuitsExplicitResolver(): void
    {
        // The privacy check runs BEFORE the explicit resolver. The fixture's
        // resolver throws if invoked; the test passes only because privacy
        // short-circuits to `null`.
        $query = <<<'GRAPHQL'
{
  parentWithClassPrivacy {
    child {
      denied_with_resolver
    }
  }
}
GRAPHQL;

        $result = $this->httpGraphql($query);

        self::assertSame([
            'data' => [
                'parentWithClassPrivacy' => [
                    'child' => [
                        'denied_with_resolver' => null,
                    ],
                ],
            ],
        ], $result);

        self::assertSame(1, DenyPrivacy::$calls);
    }

    public function testUnsupportedPrivacyValueThrows(): void
    {
        // `privacy` is documented as either a closure or a class-string. Any
        // other value (e.g. an int) is a configuration error and must throw a
        // RuntimeException at resolution time.
        $type = new class extends GraphQLType {
            protected $attributes = ['name' => 'BadPrivacyType'];

            public function fields(): array
            {
                return [
                    'broken' => [
                        'type' => Type::string(),
                        // Not callable, not a string — invalid privacy value.
                        'privacy' => 42,
                    ],
                ];
            }
        };

        $fields = $type->getFields();
        self::assertArrayHasKey('resolve', $fields['broken']);
        self::assertIsCallable($fields['broken']['resolve']);

        $resolveInfo = self::createStub(ResolveInfo::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Unsupported use of 'privacy' configuration: expected a callable or a class-string.");
        $fields['broken']['resolve'](null, [], null, $resolveInfo);
    }
}
