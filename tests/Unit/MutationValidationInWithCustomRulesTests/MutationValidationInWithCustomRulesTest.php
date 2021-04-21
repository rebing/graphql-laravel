<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\MutationValidationInWithCustomRulesTests;

use Rebing\GraphQL\Tests\TestCase;

class MutationValidationInWithCustomRulesTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'mutation' => [
                MutationWithCustomRuleWithRuleObject::class,
            ],
        ]);
    }

    public function testInPassRulePass(): void
    {
        $graphql = <<<'GRAPHQL'
mutation Mutate($arg_in_rule_pass: String) {
  mutationWithCustomRuleWithRuleObject(arg_in_rule_pass: $arg_in_rule_pass)
}
GRAPHQL;

        $result = $this->graphql($graphql, [
            'arg_in_rule_pass' => 'valid_name',
        ]);

        $expectedResult = [
            'data' => [
                'mutationWithCustomRuleWithRuleObject' => 'mutation result',
            ],
        ];
        self::assertSame($expectedResult, $result);
    }

    public function testInPassRuleFail(): void
    {
        $graphql = <<<'GRAPHQL'
mutation Mutate($arg_in_rule_fail: String) {
  mutationWithCustomRuleWithRuleObject(arg_in_rule_fail: $arg_in_rule_fail)
}
GRAPHQL;

        $result = $this->graphql($graphql, [
            'expectErrors' => true,
            'variables' => [
                'arg_in_rule_fail' => 'valid_name',
            ],
        ]);

        $expected = [
            'errors' => [
                [
                    'message' => 'validation',
                    'extensions' => [
                        'category' => 'validation',
                        'validation' => [
                            'arg_in_rule_fail' => [
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

    public function testInFailRulePass(): void
    {
        $graphql = <<<'GRAPHQL'
mutation Mutate($arg_in_rule_pass: String) {
  mutationWithCustomRuleWithRuleObject(arg_in_rule_pass: $arg_in_rule_pass)
}
GRAPHQL;

        $result = $this->graphql($graphql, [
            'expectErrors' => true,
            'variables' => [
                'arg_in_rule_pass' => 'invalid_name',
            ],
        ]);

        $expected = [
            'errors' => [
                [
                    'message' => 'validation',
                    'extensions' => [
                        'category' => 'validation',
                        'validation' => [
                            'arg_in_rule_pass' => [
                                'The selected arg in rule pass is invalid.',
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

    public function testInFailRuleFail(): void
    {
        $graphql = <<<'GRAPHQL'
mutation Mutate($arg_in_rule_fail: String) {
  mutationWithCustomRuleWithRuleObject(arg_in_rule_fail: $arg_in_rule_fail)
}
GRAPHQL;

        $result = $this->graphql($graphql, [
            'expectErrors' => true,
            'variables' => [
                'arg_in_rule_fail' => 'invalid_name',
            ],
        ]);

        $expected = [
            'errors' => [
                [
                    'message' => 'validation',
                    'extensions' => [
                        'category' => 'validation',
                        'validation' => [
                            'arg_in_rule_fail' => [
                                'The selected arg in rule fail is invalid.',
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
}
