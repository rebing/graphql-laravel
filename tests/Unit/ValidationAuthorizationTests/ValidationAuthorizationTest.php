<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ValidationAuthorizationTests;

use Rebing\GraphQL\Tests\TestCase;

class ValidationAuthorizationTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'mutation' => [
                ValidationAndAuthorizationMutation::class,
            ],
        ]);
    }

    public function testAuthorizeArgumentsInvalid(): void
    {
        $graphql = <<<'GRAPHQL'
mutation Mutate($arg1: String) {
  validationAndAuthorization(arg1: $arg1)
}
GRAPHQL;

        $result = $this->graphql($graphql, [
            'expectErrors' => true,
            'variables' => [
                'arg1' => 'invalid value',
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
                                'The selected arg1 is invalid.',
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
                        'validationAndAuthorization',
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $result);
    }

    public function testAuthorizeArgumentsValid(): void
    {
        $graphql = <<<'GRAPHQL'
mutation Mutate($arg1: String) {
  validationAndAuthorization(arg1: $arg1)
}
GRAPHQL;

        $result = $this->graphql($graphql, [
            'variables' => [
                'arg1' => 'value1',
            ],
        ]);

        $expectedResult = [
            'data' => [
                'validationAndAuthorization' => 'value',
            ],
        ];
        self::assertSame($expectedResult, $result);
    }
}
