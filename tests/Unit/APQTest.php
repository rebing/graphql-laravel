<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit;

use Illuminate\Http\UploadedFile;
use Rebing\GraphQL\Support\UploadType;
use Rebing\GraphQL\Tests\TestCase;
use Rebing\GraphQL\Tests\Unit\UploadTests\UploadMultipleFilesMutation;
use Rebing\GraphQL\Tests\Unit\UploadTests\UploadSingleFileMutation;

class APQTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $graphqlSchemasDefault = $app['config']->get('graphql.schemas.default');
        $app['config']->set('graphql.schemas.default', array_merge_recursive($graphqlSchemasDefault, [
            'mutation' => [
                UploadMultipleFilesMutation::class,
                UploadSingleFileMutation::class,
            ],
        ]));

        $graphqlTypes = $app['config']->get('graphql.types');
        $app['config']->set('graphql.types', array_merge_recursive($graphqlTypes, [
            UploadType::class
        ]));
    }

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
                ['message' => 'PersistedQueryNotSupported', 'extensions' => ['code' => 'PERSISTED_QUERY_NOT_SUPPORTED', 'category' => 'apq']],
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
                ['message' => 'PersistedQueryNotFound', 'extensions' => ['code' => 'PERSISTED_QUERY_NOT_FOUND', 'category' => 'apq']],
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
                ['message' => 'provided sha does not match query', 'extensions' => ['code' => 'INTERNAL_SERVER_ERROR', 'category' => 'apq']],
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
            ['message' => 'PersistedQueryNotSupported', 'extensions' => ['code' => 'PERSISTED_QUERY_NOT_SUPPORTED', 'category' => 'apq']],
        ], $content[0]['errors']);

        $this->assertEquals([
            ['message' => 'PersistedQueryNotSupported', 'extensions' => ['code' => 'PERSISTED_QUERY_NOT_SUPPORTED', 'category' => 'apq']],
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
            ['message' => 'PersistedQueryNotFound', 'extensions' => ['code' => 'PERSISTED_QUERY_NOT_FOUND', 'category' => 'apq']],
        ], $content[1]['errors']);

        $this->assertEquals([
            ['message' => 'provided sha does not match query', 'extensions' => ['code' => 'INTERNAL_SERVER_ERROR', 'category' => 'apq']],
        ], $content[2]['errors']);
    }

    /**
     * Test persisted query found with upload.
     */
    public function testPersistedQueryFoundWithUpload(): void
    {
        $query = 'mutation($file: Upload!) { uploadSingleFile(file: $file) }';
        $fileToUpload = UploadedFile::fake()->create('file.txt');
        $fileContent = "This is the\nuploaded\ndata";
        fwrite($fileToUpload->tempFile, $fileContent);

        // run query and persist

        $response = $this->call(
            'POST',
            '/graphql',
            [
                'operations' => json_encode([
                    'query' => $query,
                    'variables' => [
                        'file' => null,
                    ],
                    'extensions' => [
                        'persistedQuery' => [
                            'version' => 1,
                            'sha256Hash' => hash('sha256', trim($query)),
                        ],
                    ],
                ]),
                'map' => json_encode([
                    '0' => ['variables.file'],
                ]),
            ],
            [],
            ['0' => $fileToUpload],
            ['CONTENT_TYPE' => 'multipart/form-data']
        );

        $this->assertEquals(200, $response->getStatusCode());

        $content = $response->json();

        $this->assertArrayHasKey('data', $content);
        $this->assertEquals(['uploadSingleFile' => $fileContent], $content['data']);

        // run persisted query

        $response = $this->call(
            'POST',
            '/graphql',
            [
                'operations' => json_encode([
                    'variables' => [
                        'file' => null,
                    ],
                    'extensions' => [
                        'persistedQuery' => [
                            'version' => 1,
                            'sha256Hash' => hash('sha256', trim($query)),
                        ],
                    ],
                ]),
                'map' => json_encode([
                    '0' => ['variables.file'],
                ]),
                'extensions' => [
                    'persistedQuery' => [
                        'version' => 1,
                        'sha256Hash' => hash('sha256', trim($this->queries['examples'])),
                    ],
                ],
            ],
            [],
            ['0' => $fileToUpload],
            ['CONTENT_TYPE' => 'multipart/form-data']
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertArrayHasKey('data', $content);
        $this->assertEquals(['uploadSingleFile' => $fileContent], $content['data']);
    }
}
