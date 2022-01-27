<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit;

use Rebing\GraphQL\Tests\TestCase;

class EmptyRoutePrefixTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.route.prefix', '');
    }

    public function testEmptyRoutePrefix():void
    {
        $response = $this->call('GET', '/', [
            'query' => $this->queries['examples'],
        ]);

        self::assertEquals(200, $response->getStatusCode());
    }
}
