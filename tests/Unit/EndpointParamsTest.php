<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit;

use Rebing\GraphQL\Tests\TestCase;

class EndpointParamsTest extends TestCase
{
    public function testGetDefaultSchemaWithRouteParameter(): void
    {
        $response = $this->call('GET', '/graphql/arbitrary_param', [
            'query' => $this->queries['examples'],
        ]);

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->getData(true);
        self::assertArrayHasKey('data', $content);
        self::assertEquals($content['data'], [
            'examples' => $this->data,
        ]);
    }
    public function testGetCustomSchemaWithRouteParameter(): void
    {
        $response = $this->call('GET', '/graphql/arbitrary_param/custom', [
            'query' => $this->queries['examplesCustom'],
        ]);
        self::assertEquals(200, $response->getStatusCode());
        $content = $response->getData(true);
        self::assertArrayHasKey('data', $content);
        self::assertEquals($content['data'], [
            'examplesCustom' => $this->data,
        ]);
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);
        $app['config']->set('graphql.route.prefix', 'graphql/{parameter}');
    }
}
