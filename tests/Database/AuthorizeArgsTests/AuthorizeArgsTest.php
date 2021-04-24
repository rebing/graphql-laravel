<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\AuthorizeArgsTests;

use Rebing\GraphQL\Tests\TestCaseDatabase;

class AuthorizeArgsTest extends TestCaseDatabase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'query' => [
                TestAuthorizationArgsQuery::class,
            ],
        ]);
    }

    public function testAuthorizeArgs(): void
    {
        $graphql = <<<'GRAPHQL'
query {
  testAuthorizationArgs(id: "foobar")
}
GRAPHQL;

        // All relevant test assertions are in \Rebing\GraphQL\Tests\Database\AuthorizeArgsTests\TestAuthorizationArgsQuery::authorize
        $this->httpGraphql($graphql);
    }
}
