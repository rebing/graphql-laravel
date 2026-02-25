<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\MutationValidationUniqueWithCustomRulesTests;

use Rebing\GraphQL\Tests\Support\Models\User;
use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;
use Rebing\GraphQL\Tests\TestCaseDatabase;

class MutationValidationUniqueWithCustomRulesTest extends TestCaseDatabase
{
    use SqlAssertionTrait;

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'mutation' => [
                MutationWithCustomRuleWithRuleObject::class,
            ],
        ]);
    }

    public function testUniquePassRulePass(): void
    {
        /* @var User $user */
        User::factory()
            ->create([
                'name' => 'name_unique',
            ]);

        $graphql = <<<'GRAPHQL'
mutation Mutate($arg_unique_rule_pass: String) {
  mutationWithCustomRuleWithRuleObject(arg_unique_rule_pass: $arg_unique_rule_pass)
}
GRAPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($graphql, [
            'expectErrors' => true,
            'variables' => [
                'arg_unique_rule_pass' => 'another_name',
            ],
        ]);

        $this->assertSqlQueries(
            <<<'SQL'
select count(*) as aggregate from "users" where "name" = ?;
SQL,
        );

        $expectedResult = [
            'data' => [
                'mutationWithCustomRuleWithRuleObject' => 'mutation result',
            ],
        ];
        self::assertSame($expectedResult, $result);
    }

    public function testUniqueFailRulePass(): void
    {
        /* @var User $user */
        User::factory()
            ->create([
                'name' => 'name_unique',
            ]);

        $graphql = <<<'GRAPHQL'
mutation Mutate($arg_unique_rule_pass: String) {
  mutationWithCustomRuleWithRuleObject(arg_unique_rule_pass: $arg_unique_rule_pass)
}
GRAPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($graphql, [
            'expectErrors' => true,
            'variables' => [
                'arg_unique_rule_pass' => 'name_unique',
            ],
        ]);

        $this->assertSqlQueries(
            <<<'SQL'
select count(*) as aggregate from "users" where "name" = ?;
SQL,
        );

        $expected = [
            'errors' => [
                [
                    'message' => 'validation',
                    'extensions' => [
                        'category' => 'validation',
                        'validation' => [
                            'arg_unique_rule_pass' => [
                                'The arg unique rule pass has already been taken.',
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
                        'mutationWithCustomRuleWithRuleObject',
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $result);
    }

    public function testUniquePassRuleFail(): void
    {
        /* @var User $user */
        User::factory()
            ->create([
                'name' => 'name_unique',
            ]);

        $graphql = <<<'GRAPHQL'
mutation Mutate($arg_unique_rule_fail: String) {
  mutationWithCustomRuleWithRuleObject(arg_unique_rule_fail: $arg_unique_rule_fail)
}
GRAPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($graphql, [
            'expectErrors' => true,
            'variables' => [
                'arg_unique_rule_fail' => 'another_name',
            ],
        ]);

        $expected = [
            'errors' => [
                [
                    'message' => 'validation',
                    'extensions' => [
                        'category' => 'validation',
                        'validation' => [
                            'arg_unique_rule_fail' => [
                                'rule object validation fails',
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
                        'mutationWithCustomRuleWithRuleObject',
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $result);
    }

    public function testUniqueFailRuleFail(): void
    {
        /* @var User $user */
        User::factory()
            ->create([
                'name' => 'name_unique',
            ]);

        $graphql = <<<'GRAPHQL'
mutation Mutate($arg_unique_rule_fail: String) {
  mutationWithCustomRuleWithRuleObject(arg_unique_rule_fail: $arg_unique_rule_fail)
}
GRAPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($graphql, [
            'expectErrors' => true,
            'variables' => [
                'arg_unique_rule_fail' => 'name_unique',
            ],
        ]);

        $this->assertSqlQueries(
            <<<'SQL'
select count(*) as aggregate from "users" where "name" = ?;
SQL,
        );

        $expected = [
            'errors' => [
                [
                    'message' => 'validation',
                    'extensions' => [
                        'category' => 'validation',
                        'validation' => [
                            'arg_unique_rule_fail' => [
                                'The arg unique rule fail has already been taken.',
                                'rule object validation fails',
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
                        'mutationWithCustomRuleWithRuleObject',
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $result);
    }

    public function testErrorExtension(): void
    {
        /* @var User $user */
        User::factory()
            ->create([
                'name' => 'name_unique',
            ]);

        $graphql = <<<'GRAPHQL'
mutation Mutate($arg_unique_rule_fail: String) {
  mutationWithCustomRuleWithRuleObject(arg_unique_rule_fail: $arg_unique_rule_fail)
}
GRAPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($graphql, [
            'expectErrors' => true,
            'variables' => [
                'arg_unique_rule_fail' => 'name_unique',
            ],
        ]);

        self::assertSame('validation', $result['errors'][0]['extensions']['category']);
    }
}
