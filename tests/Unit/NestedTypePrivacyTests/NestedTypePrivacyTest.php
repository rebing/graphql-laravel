<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\NestedTypePrivacyTests;

use GraphQL\Executor\Executor;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Tests\TestCase;

/**
 * Regression test for https://github.com/rebing/graphql-laravel/issues/1161
 *
 * Privacy was only enforced when using SelectFields. When a type was nested
 * inside another type (sub-type), the privacy attribute was silently ignored.
 * With the fix, privacy is enforced at the Type::getFields() level via
 * resolver wrapping, so it works universally regardless of nesting depth
 * or whether SelectFields is used.
 */
class NestedTypePrivacyTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('graphql.schemas.default', [
            'query' => [
                ParentQuery::class,
            ],
        ]);

        $app['config']->set('graphql.types', [
            ParentType::class,
            ChildType::class,
        ]);
    }

    public function testPrivacyDeniedOnNestedSubType(): void
    {
        $query = <<<'GRAPHQL'
{
  parent {
    name
    child {
      public_name
      secret_name
    }
  }
}
GRAPHQL;

        $result = $this->httpGraphql($query);

        $expectedResult = [
            'data' => [
                'parent' => [
                    'name' => 'parent name',
                    'child' => [
                        'public_name' => 'public value',
                        'secret_name' => null,
                    ],
                ],
            ],
        ];
        self::assertEquals($expectedResult, $result);
    }

    public function testPrivacyAllowedOnNestedSubType(): void
    {
        $query = <<<'GRAPHQL'
{
  parent {
    name
    child {
      public_name
      allowed_name
    }
  }
}
GRAPHQL;

        $result = $this->httpGraphql($query);

        $expectedResult = [
            'data' => [
                'parent' => [
                    'name' => 'parent name',
                    'child' => [
                        'public_name' => 'public value',
                        'allowed_name' => 'allowed value',
                    ],
                ],
            ],
        ];
        self::assertEquals($expectedResult, $result);
    }

    public function testPrivacyAllowedUsesConfiguredDefaultFieldResolver(): void
    {
        $this->app['config']->set(
            'graphql.defaultFieldResolver',
            static function (mixed $root, array $args, mixed $context, ResolveInfo $info): mixed {
                $value = Executor::defaultFieldResolver($root, $args, $context, $info);

                return \is_string($value) ? strtoupper($value) : $value;
            },
        );

        $query = <<<'GRAPHQL'
{
  parent {
    child {
      allowed_name
    }
  }
}
GRAPHQL;

        $result = $this->httpGraphql($query);

        self::assertSame('ALLOWED VALUE', $result['data']['parent']['child']['allowed_name']);
    }

    public function testPrivacyMixedOnNestedSubType(): void
    {
        $query = <<<'GRAPHQL'
{
  parent {
    name
    child {
      public_name
      secret_name
      allowed_name
    }
  }
}
GRAPHQL;

        $result = $this->httpGraphql($query);

        $expectedResult = [
            'data' => [
                'parent' => [
                    'name' => 'parent name',
                    'child' => [
                        'public_name' => 'public value',
                        'secret_name' => null,
                        'allowed_name' => 'allowed value',
                    ],
                ],
            ],
        ];
        self::assertEquals($expectedResult, $result);
    }
}
