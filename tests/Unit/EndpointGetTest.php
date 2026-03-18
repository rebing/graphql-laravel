<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit;

use Rebing\GraphQL\Tests\TestCase;

/**
 * Tests for GET endpoint behavior when GET is explicitly enabled.
 *
 * These tests ensure GET requests continue to work correctly when users
 * opt in to GET support via the schema 'method' config. The default
 * config is POST-only, but GET can be enabled explicitly.
 */
class EndpointGetTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default.method', ['GET', 'POST']);
        $app['config']->set('graphql.schemas.custom.method', ['GET', 'POST']);
    }

    public function testGetDefaultSchema(): void
    {
        $response = $this->call('GET', '/graphql', [
            'query' => $this->queries['examples'],
        ]);

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->getData(true);
        self::assertArrayHasKey('data', $content);
        self::assertEquals($content['data'], [
            'examples' => $this->data,
        ]);
    }

    public function testGetCustomSchema(): void
    {
        $response = $this->call('GET', '/graphql/custom', [
            'query' => $this->queries['examplesCustom'],
        ]);

        $content = $response->getData(true);
        self::assertArrayHasKey('data', $content);
        self::assertEquals($content['data'], [
            'examplesCustom' => $this->data,
        ]);
    }

    public function testGetWithVariables(): void
    {
        $response = $this->call('GET', '/graphql', [
            'query' => $this->queries['examplesWithVariables'],
            'variables' => [
                'index' => 0,
            ],
        ]);

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->getData(true);
        self::assertArrayHasKey('data', $content);
        self::assertEquals($content['data'], [
            'examples' => [
                $this->data[0],
            ],
        ]);
    }

    public function testGetWithVariablesSerialized(): void
    {
        $response = $this->call('GET', '/graphql', [
            'query' => $this->queries['examplesWithVariables'],
            'variables' => \Safe\json_encode([
                'index' => 0,
            ]),
        ]);

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->getData(true);
        self::assertArrayHasKey('data', $content);
        self::assertEquals($content['data'], [
            'examples' => [
                $this->data[0],
            ],
        ]);
    }

    public function testGetUnauthorized(): void
    {
        $response = $this->call('GET', '/graphql', [
            'query' => $this->queries['examplesWithAuthorize'],
        ]);

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->getData(true);
        self::assertArrayHasKey('data', $content);
        self::assertArrayHasKey('errors', $content);
        self::assertEquals('Unauthorized', $content['errors'][0]['message']);
        self::assertNull($content['data']['examplesAuthorize']);
    }

    public function testGetUnauthorizedWithCustomError(): void
    {
        $response = $this->call('GET', '/graphql', [
            'query' => $this->queries['examplesWithAuthorizeMessage'],
        ]);

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->getData(true);
        self::assertArrayHasKey('data', $content);
        self::assertArrayHasKey('errors', $content);
        self::assertEquals('You are not authorized to perform this action', $content['errors'][0]['message']);
        self::assertNull($content['data']['examplesAuthorizeMessage']);
    }

    public function testBatchedQueriesDontWorkWithGet(): void
    {
        $this->app['config']->set('graphql.batching.enable', true);

        $response = $this->call('GET', '/graphql', [
            [
                'query' => $this->queries['examplesWithVariables'],
                'variables' => [
                    'index' => 0,
                ],
            ],
            [
                'query' => $this->queries['examplesWithVariables'],
                'variables' => [
                    'index' => 0,
                ],
            ],
        ]);

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->getData(true);

        unset($content['errors'][0]['extensions']['file']);
        unset($content['errors'][0]['extensions']['line']);
        unset($content['errors'][0]['extensions']['trace']);

        $expected = [
            'errors' => [
                [
                    'message' => 'GraphQL Request must include at least one of those two parameters: "query" or "queryId"',
                    'extensions' => [
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $content);
    }
}
