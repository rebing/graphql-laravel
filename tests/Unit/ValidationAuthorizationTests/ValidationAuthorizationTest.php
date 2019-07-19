<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\ValidationAuthorizationTests;

use Rebing\GraphQL\Tests\TestCase;

class ValidationAuthorizationTest extends TestCase
{
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

        $this->assertSame('Unauthorized', $result['errors'][0]['message']);
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
        $this->assertSame($expectedResult, $result);
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'mutation' => [
                ValidationAndAuthorizationMutation::class,
            ],
        ]);
    }
}
