<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\AuthenticateArgsTest;

use Rebing\GraphQL\Tests\TestCaseDatabase;

class AuthenticateArgsTest extends TestCaseDatabase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'query' => [
                TestAuthenticationArgsQuery::class,
            ],
        ]);
    }

    public function testAuthorizeArgs(): void
    {
        $graphql = <<<'GRAPHQL'
query {
  testAuthenticationArgs(id: "foobar")
}
GRAPHQL;

        // All relevant test assertions are in \Rebing\GraphQL\Tests\Database\AuthenticateArgsTests\TestAuthenticationArgsQuery::authenticate
        $this->httpGraphql($graphql, [
            'expectErrors' => true,
        ]);
    }
}
