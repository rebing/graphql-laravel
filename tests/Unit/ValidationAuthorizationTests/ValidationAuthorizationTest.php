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

        $result = $this->httpGraphql($graphql, [
            'expectErrors' => true,
            'variables' => [
                'arg1' => 'invalid value',
            ],
        ]);

        // Authorization runs before validation, so an unauthorized request
        // is rejected without exposing validation details
        $expected = [
            'errors' => [
                [
                    'message' => 'Unauthorized',
                    'extensions' => [
                        'category' => 'authorization',
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

        $result = $this->httpGraphql($graphql, [
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
