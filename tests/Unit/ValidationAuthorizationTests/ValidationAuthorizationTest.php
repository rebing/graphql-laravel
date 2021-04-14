<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ValidationAuthorizationTests;

use Illuminate\Support\MessageBag;
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

        self::assertSame('validation', $result['errors'][0]['message']);

        /** @var MessageBag $messageBag */
        $messageBag = $result['errors'][0]['extensions']['validation'];
        $expectedErrors = [
            'arg1' => [
                'The selected arg1 is invalid.',
            ],
        ];
        self::assertSame($expectedErrors, $messageBag->messages());
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

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'mutation' => [
                ValidationAndAuthorizationMutation::class,
            ],
        ]);
    }
}
