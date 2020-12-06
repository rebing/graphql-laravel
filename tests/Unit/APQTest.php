<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit;

use Rebing\GraphQL\Tests\TestCase;

class APQTest extends TestCase
{
    /**
     * Test persisted query not supported.
     */
    public function testPersistedQueryNotSupported(): void
    {
        config(['graphql.apq.enable' => false]);

        $response = $this->call('GET', '/graphql', [
            'extensions' => [
                'persistedQuery' => [
                    'version' => 1,
                    'sha256Hash' => hash('sha256', trim($this->queries['examples'])),
                ],
            ],
        ]);

        $this->assertEquals(200, $response->getStatusCode());

        $content = $response->json();

        $this->assertEquals([
            'errors' => [
                ['message' => 'PersistedQueryNotSupported', 'extensions' => ['code' => 'PERSISTED_QUERY_NOT_SUPPORTED']],
            ],
        ], $content);
    }

    /**
     * Test persisted query not found.
     */
    public function testPersistedQueryNotFound(): void
    {
        $response = $this->call('GET', '/graphql', [
            'extensions' => [
                'persistedQuery' => [
                    'version' => 1,
                    'sha256Hash' => hash('sha256', trim($this->queries['examples'])),
                ],
            ],
        ]);

        $this->assertEquals(200, $response->getStatusCode());

        $content = $response->json();

        $this->assertEquals([
            'errors' => [
                ['message' => 'PersistedQueryNotFound', 'extensions' => ['code' => 'PERSISTED_QUERY_NOT_FOUND']],
            ],
        ], $content);
    }

    /**
     * Test persisted query found.
     */
    public function testPersistedQueryFound(): void
    {
        // run query and persist

        $response = $this->call('GET', '/graphql', [
            'query' => trim($this->queries['examples']),
            'extensions' => [
                'persistedQuery' => [
                    'version' => 1,
                    'sha256Hash' => hash('sha256', trim($this->queries['examples'])),
                ],
            ],
        ]);

        $this->assertEquals(200, $response->getStatusCode());

        $content = $response->json();

        $this->assertArrayHasKey('data', $content);
        $this->assertEquals(['examples' => $this->data], $content['data']);

        // run persisted query

        $response = $this->call('GET', '/graphql', [
            'extensions' => [
                'persistedQuery' => [
                    'version' => 1,
                    'sha256Hash' => hash('sha256', trim($this->queries['examples'])),
                ],
            ],
        ]);

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertArrayHasKey('data', $content);
        $this->assertEquals(['examples' => $this->data], $content['data']);
    }

    /**
     * Test persisted query invalid hash.
     */
    public function testPersistedQueryInvalidHash(): void
    {
        $response = $this->call('GET', '/graphql', [
            'query' => trim($this->queries['examples']),
            'extensions' => [
                'persistedQuery' => [
                    'version' => 1,
                    'sha256Hash' => hash('sha256', 'foo'),
                ],
            ],
        ]);

        $this->assertEquals(200, $response->getStatusCode());

        $content = $response->json();

        $this->assertEquals([
            'errors' => [
                ['message' => 'provided sha does not match query', 'extensions' => ['code' => 'INTERNAL_SERVER_ERROR']],
            ],
        ], $content);
    }

    /**
     * Test persisted query batching not supported.
     */
    public function testPersistedQueryBatchingNotSupported(): void
    {
        config(['graphql.apq.enable' => false]);

        $response = $this->call('GET', '/graphql', [
            [
                'variables' => [
                    'index' => 0,
                ],
                'extensions' => [
                    'persistedQuery' => [
                        'version' => 1,
                        'sha256Hash' => hash('sha256', trim($this->queries['examplesWithVariables'])),
                    ],
                ],
            ],
            [
                'query' => trim($this->queries['examples']),
                'extensions' => [
                    'persistedQuery' => [
                        'version' => 1,
                        'sha256Hash' => hash('sha256', trim($this->queries['examples'])),
                    ],
                ],
            ],
        ]);

        $this->assertEquals(200, $response->getStatusCode());

        $content = $response->json();

        $this->assertArrayHasKey(0, $content);
        $this->assertArrayHasKey(1, $content);

        $this->assertEquals([
            ['message' => 'PersistedQueryNotSupported', 'extensions' => ['code' => 'PERSISTED_QUERY_NOT_SUPPORTED']],
        ], $content[0]['errors']);

        $this->assertEquals([
            ['message' => 'PersistedQueryNotSupported', 'extensions' => ['code' => 'PERSISTED_QUERY_NOT_SUPPORTED']],
        ], $content[1]['errors']);
    }

    /**
     * Test persisted query batching found, not found and invalid hash.
     */
    public function testPersistedQueryBatchingFoundNotFoundAndInvalidHash(): void
    {
        // run query and persist

        $response = $this->call('GET', '/graphql', [
            'query' => trim($this->queries['examples']),
            'extensions' => [
                'persistedQuery' => [
                    'version' => 1,
                    'sha256Hash' => hash('sha256', trim($this->queries['examples'])),
                ],
            ],
        ]);

        $this->assertEquals(200, $response->getStatusCode());

        $content = $response->json();

        $this->assertArrayHasKey('data', $content);
        $this->assertEquals(['examples' => $this->data], $content['data']);

        // run query persisted and not

        $response = $this->call('GET', '/graphql', [
            [
                'extensions' => [
                    'persistedQuery' => [
                        'version' => 1,
                        'sha256Hash' => hash('sha256', trim($this->queries['examples'])),
                    ],
                ],
            ],
            [
                'variables' => [
                    'index' => 0,
                ],
                'extensions' => [
                    'persistedQuery' => [
                        'version' => 1,
                        'sha256Hash' => hash('sha256', trim($this->queries['examplesWithVariables'])),
                    ],
                ],
            ],
            [
                'query' => trim($this->queries['examplesWithVariables']),
                'variables' => [
                    'index' => 0,
                ],
                'extensions' => [
                    'persistedQuery' => [
                        'version' => 1,
                        'sha256Hash' => hash('sha256', 'foo'),
                    ],
                ],
            ],
        ]);

        $this->assertEquals(200, $response->getStatusCode());

        $content = $response->json();

        $this->assertArrayHasKey(0, $content);
        $this->assertArrayHasKey(1, $content);
        $this->assertArrayHasKey(2, $content);

        $this->assertArrayHasKey('data', $content[0]);
        $this->assertEquals(['examples' => $this->data], $content[0]['data']);

        $this->assertEquals([
            ['message' => 'PersistedQueryNotFound', 'extensions' => ['code' => 'PERSISTED_QUERY_NOT_FOUND']],
        ], $content[1]['errors']);

        $this->assertEquals([
            ['message' => 'provided sha does not match query', 'extensions' => ['code' => 'INTERNAL_SERVER_ERROR']],
        ], $content[2]['errors']);
    }
}
