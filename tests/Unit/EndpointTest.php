<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit;

use Rebing\GraphQL\Tests\TestCase;

class EndpointTest extends TestCase
{
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
            'variables' => json_encode([
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

    public function testBatchedQueries(): void
    {
        $response = $this->call('POST', '/graphql', [
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
        self::assertArrayHasKey(0, $content);
        self::assertArrayHasKey(1, $content);
        self::assertEquals($content[0]['data'], [
            'examples' => [
                $this->data[0],
            ],
        ]);
        self::assertEquals($content[1]['data'], [
            'examples' => [
                $this->data[0],
            ],
        ]);
    }

    public function testBatchedQueriesDontWorkWithGet(): void
    {
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
        unset($content['errors'][0]['trace']);

        $expected = [
            'errors' => [
                [
                    'message' => 'GraphQL Request must include at least one of those two parameters: "query" or "queryId"',
                    'extensions' => [
                        'category' => 'request',
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $content);
    }

    public function testBatchedQueriesButBatchingDisabled(): void
    {
        config(['graphql.batching.enable' => false]);

        $response = $this->call('POST', '/graphql', [
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

        $actual = $response->getData(true);

        $expected = [
            [
                'errors' => [
                    [
                        'message' => 'Batch request received but batching is not supported',
                    ],
                ],
            ],
            [
                'errors' => [
                    [
                        'message' => 'Batch request received but batching is not supported',
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $actual);
    }

    public function testGetGraphqiQL(): void
    {
        $response = $this->call('GET', '/graphiql');

        // Are we seeing the right template?
        $response->assertSee('This GraphiQL example illustrates how to use some of GraphiQL\'s props', false);
        // The argument to fetch is extracted from the configuration
        $response->assertSee('return fetch(\'/graphql\', {', false);
        $response->assertSee("'x-csrf-token': xcsrfToken || ''", false);
    }
}
