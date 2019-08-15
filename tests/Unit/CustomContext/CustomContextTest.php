<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\CustomContext;

use Rebing\GraphQL\Tests\TestCase;

class CustomContextTest extends TestCase
{
    public function testRequestAssertedInQuery(): void
    {
        $response = $this->call('GET', '/graphql', [
            'query' => '{ queryReceivingRequestInContext }',
        ]);

        $this->assertEquals($response->getStatusCode(), 200);

        $expectedResult = [
            'data' => [
                'queryReceivingRequestInContext' => 'The URL used for the GraphQL request: http://localhost/graphql',
            ],
        ];
        $this->assertSame($expectedResult, $response->getData(true));
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.controllers', CustomGraphQLController::class.'@query');

        $app['config']->set('graphql.schemas.default', [
            'query' => [
                QueryReceivingRequestInContextQuery::class,
            ],
        ]);
    }
}
