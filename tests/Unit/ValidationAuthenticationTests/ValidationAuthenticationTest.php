<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ValidationAuthenticationTests;

use Rebing\GraphQL\Tests\TestCase;

class ValidationAuthenticationTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'mutation' => [
                ValidationAndAuthenticateMutation::class,
            ],
        ]);
    }

    public function testAuthorizeArgumentsInvalid(): void
    {
        $graphql = <<<'GRAPHQL'
mutation Mutate($arg1: String) {
  validationAndAuthentication(arg1: $arg1)
}
GRAPHQL;

        $result = $this->httpGraphql($graphql, [
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
                        'validationAndAuthentication',
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
  validationAndAuthentication(arg1: $arg1)
}
GRAPHQL;

        $result = $this->httpGraphql($graphql, [
            'variables' => [
                'arg1' => 'value1',
            ],
        ]);

        $expectedResult = [
            'data' => [
                'validationAndAuthentication' => 'value',
            ],
        ];
        self::assertSame($expectedResult, $result);
    }
}
