<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\MutationCustomRulesTests;

use Rebing\GraphQL\Tests\TestCase;

class MutationCustomRulesTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'mutation' => [
                MutationWithCustomRuleWithClosure::class,
                MutationWithCustomRuleWithRuleObject::class,
            ],
        ]);
    }

    public function testMutationWithCustomRuleWithClosure(): void
    {
        $graphql = <<<'GRAPHQL'
mutation Mutate($arg1: String) {
  mutationWithCustomRuleWithClosure(arg1: $arg1)
}
GRAPHQL;

        $result = $this->httpGraphql($graphql, [
            'expectErrors' => true,
            'variables' => [
                'arg1' => 'Test argument 1',
            ],
        ]);

        $expected = [
            'errors' => [
                [
                    'message' => 'validation',
                    'extensions' => [
                        'category' => 'validation',
                        'validation' => [
                            'arg1' => [
                                'arg1 is invalid',
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
                        'mutationWithCustomRuleWithClosure',
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $result);
    }

    public function testMutationWithCustomRuleWithRuleObject(): void
    {
        $graphql = <<<'GRAPHQL'
mutation Mutate($arg1: String) {
  mutationWithCustomRuleWithRuleObject(arg1: $arg1)
}
GRAPHQL;

        $result = $this->httpGraphql($graphql, [
            'expectErrors' => true,
            'variables' => [
                'arg1' => 'Test argument 1',
            ],
        ]);

        $expected = [
            'errors' => [
                [
                    'message' => 'validation',
                    'extensions' => [
                        'category' => 'validation',
                        'validation' => [
                            'arg1' => [
                                'arg1 is invalid',
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
