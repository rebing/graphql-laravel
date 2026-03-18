<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ValidationAuthorizationTests;

use Rebing\GraphQL\Tests\TestCase;

/**
 * Ensures validation rules use the original argument names (before alias
 * mapping), not the aliased names. If getArgs() (alias mapping) were to
 * run before validation, rules keyed on original names would silently
 * stop matching, and validation would pass when it should fail.
 */
class AliasedArgValidationTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'mutation' => [
                AliasedArgValidationMutation::class,
            ],
        ]);
    }

    public function testValidationUsesOriginalArgNameNotAlias(): void
    {
        $graphql = <<<'GRAPHQL'
mutation Mutate($testArg: String) {
  aliasedArgValidation(test_arg: $testArg)
}
GRAPHQL;

        $result = $this->httpGraphql($graphql, [
            'expectErrors' => true,
            'variables' => [
                'testArg' => 'invalid_value',
            ],
        ]);

        // The validation rule 'in:valid_value' is defined on 'test_arg'.
        // Validation must run on the original arg name so the rule matches.
        // If alias mapping ran first, 'test_arg' would already be renamed to
        // 'test_alias', the rule keyed on 'test_arg' would not match, and
        // this assertion would fail because resolve() would execute instead.
        $expected = [
            'errors' => [
                [
                    'message' => 'validation',
                    'extensions' => [
                        'category' => 'validation',
                        'validation' => [
                            'test_arg' => [
                                'The selected test arg is invalid.',
                            ],
                        ],
                    ],
                    'locations' => [
                        [
                            'line' => 2,
                            'column' => 3,
                        ],
                    ],
                    'path' => [
                        'aliasedArgValidation',
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $result);
    }

    public function testValidValuePassesValidationAndResolvesWithAlias(): void
    {
        $graphql = <<<'GRAPHQL'
mutation Mutate($testArg: String) {
  aliasedArgValidation(test_arg: $testArg)
}
GRAPHQL;

        $result = $this->httpGraphql($graphql, [
            'variables' => [
                'testArg' => 'valid_value',
            ],
        ]);

        // With a valid value, validation passes, alias mapping runs,
        // and resolve() sees the aliased key 'test_alias'
        $expected = [
            'data' => [
                'aliasedArgValidation' => 'valid_value',
            ],
        ];
        self::assertSame($expected, $result);
    }
}
