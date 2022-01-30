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

    public function testEmptyRoutePrefix(): void
    {
        $response = $this->call('GET', '/', [
            'query' => $this->queries['examples'],
        ]);

        self::assertEquals(200, $response->getStatusCode());
    }

    public function testGetCustomSchema(): void
    {
        $response = $this->call('GET', '/custom', [
            'query' => $this->queries['examplesCustom'],
        ]);

        self::assertEquals(200, $response->getStatusCode());
    }

    public function testGetGraphiQL(): void
    {
        $response = $this->call('GET', '/graphiql');

        $response->assertSee('return fetch(\'/\', {', false);
    }

    public function testGetGraphiQLCustomSchema(): void
    {
        $response = $this->call('GET', '/graphiql/custom');

        $response->assertSee('return fetch(\'/custom\', {', false);
    }
}
