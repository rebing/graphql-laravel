<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\AuthorizeArgsTests;

use Rebing\GraphQL\Tests\TestCaseDatabase;

class AuthorizeArgsTest extends TestCaseDatabase
{
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

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.controllers', GraphQLController::class.'@query');

        $app['config']->set('graphql.schemas.default', [
            'query' => [
                TestAuthorizationArgsQuery::class,
            ],
        ]);
    }
}
