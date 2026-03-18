<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit;

use Rebing\GraphQL\Tests\TestCase;

class EndpointTest extends TestCase
{
    public function testPostDefaultSchema(): void
    {
        $response = $this->call('POST', '/graphql', [
            'query' => $this->queries['examples'],
        ]);

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->getData(true);
        self::assertArrayHasKey('data', $content);
        self::assertEquals($content['data'], [
            'examples' => $this->data,
        ]);
    }

    public function testPostCustomSchema(): void
    {
        $response = $this->call('POST', '/graphql/custom', [
            'query' => $this->queries['examplesCustom'],
        ]);

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->getData(true);
        self::assertArrayHasKey('data', $content);
        self::assertEquals($content['data'], [
            'examplesCustom' => $this->data,
        ]);
    }

    public function testPostWithVariables(): void
    {
        $response = $this->call('POST', '/graphql', [
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

    public function testPostWithVariablesSerialized(): void
    {
        $response = $this->call('POST', '/graphql', [
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

    public function testPostUnauthorized(): void
    {
        $response = $this->call('POST', '/graphql', [
            'query' => $this->queries['examplesWithAuthorize'],
        ]);

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->getData(true);
        self::assertArrayHasKey('data', $content);
        self::assertArrayHasKey('errors', $content);
        self::assertEquals('Unauthorized', $content['errors'][0]['message']);
        self::assertNull($content['data']['examplesAuthorize']);
    }

    public function testPostUnauthorizedWithCustomError(): void
    {
        $response = $this->call('POST', '/graphql', [
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
        $this->app['config']->set('graphql.batching.enable', true);

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

    public function testGetRequestsAreRejected(): void
    {
        $response = $this->call('GET', '/graphql', [
            'query' => $this->queries['examples'],
        ]);

        self::assertEquals(405, $response->getStatusCode());
    }

    public function testBatchedQueriesButBatchingDisabled(): void
    {
        $this->app['config']->set(['graphql.batching.enable' => false]);

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

    public function testBatchedQueriesExceedingMaxBatchSize(): void
    {
        $this->app['config']->set('graphql.batching.enable', true);
        $this->app['config']->set('graphql.batching.max_batch_size', 1);

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
                        'message' => 'Batch of 2 exceeds the maximum of 1 operation',
                    ],
                ],
            ],
            [
                'errors' => [
                    [
                        'message' => 'Batch of 2 exceeds the maximum of 1 operation',
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $actual);
    }

    public function testBatchedQueriesWithinMaxBatchSize(): void
    {
        $this->app['config']->set('graphql.batching.enable', true);
        $this->app['config']->set('graphql.batching.max_batch_size', 5);

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
        self::assertArrayHasKey('data', $content[0]);
        self::assertArrayHasKey('data', $content[1]);
    }

    public function testBatchedQueriesWithNullMaxBatchSizeAllowsUnlimited(): void
    {
        $this->app['config']->set('graphql.batching.enable', true);
        $this->app['config']->set('graphql.batching.max_batch_size', null);

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
        self::assertArrayHasKey('data', $content[0]);
        self::assertArrayHasKey('data', $content[1]);
    }
}
