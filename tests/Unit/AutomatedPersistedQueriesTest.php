<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit;

use Illuminate\Http\UploadedFile;
use Rebing\GraphQL\Error\AutomaticPersistedQueriesError;
use Rebing\GraphQL\Support\UploadType;
use Rebing\GraphQL\Tests\TestCase;
use Rebing\GraphQL\Tests\Unit\UploadTests\UploadMultipleFilesMutation;
use Rebing\GraphQL\Tests\Unit\UploadTests\UploadSingleFileMutation;

class AutomatedPersistedQueriesTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
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
            UploadType::class,
        ]));

        $app['config']->set(['graphql.apq.enable' => true]);
    }

    public function testPersistedQueryNotSupported(): void
    {
        $this->app['config']->set(['graphql.apq.enable' => false]);

        $response = $this->call('GET', '/graphql', [
            'extensions' => [
                'persistedQuery' => [
                    'version' => 1,
                    'sha256Hash' => hash('sha256', trim($this->queries['examples'])),
                ],
            ],
        ]);

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->json();

        unset($content['errors'][0]['extensions']['file']);
        unset($content['errors'][0]['extensions']['line']);

        self::assertEquals([
            'errors' => [
                [
                    'message' => AutomaticPersistedQueriesError::MESSAGE_PERSISTED_QUERY_NOT_SUPPORTED,
                    'extensions' => [
                        'code' => AutomaticPersistedQueriesError::CODE_PERSISTED_QUERY_NOT_SUPPORTED,
                        'category' => AutomaticPersistedQueriesError::CATEGORY_APQ,
                    ],
                ],
            ],
        ], $content);
    }

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

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->json();

        unset($content['errors'][0]['extensions']['file']);
        unset($content['errors'][0]['extensions']['line']);

        self::assertEquals([
            'errors' => [
                [
                    'message' => AutomaticPersistedQueriesError::MESSAGE_PERSISTED_QUERY_NOT_FOUND,
                    'extensions' => [
                        'code' => AutomaticPersistedQueriesError::CODE_PERSISTED_QUERY_NOT_FOUND,
                        'category' => AutomaticPersistedQueriesError::CATEGORY_APQ,
                    ],
                ],
            ],
        ], $content);
    }

    public function testPersistedQueryFound(): void
    {
        $hash = hash('sha256', trim($this->queries['examples']));

        // run query and persist

        $response = $this->call('GET', '/graphql', [
            'query' => trim($this->queries['examples']),
            'extensions' => [
                'persistedQuery' => [
                    'version' => 1,
                    'sha256Hash' => $hash,
                ],
            ],
        ]);

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->json();

        self::assertArrayHasKey('data', $content);
        self::assertEquals(['examples' => $this->data], $content['data']);

        // run persisted query using POST

        $response = $this->call('POST', '/graphql', [
            'extensions' => [
                'persistedQuery' => [
                    'version' => 1,
                    'sha256Hash' => $hash,
                ],
            ],
        ]);

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->json();

        self::assertArrayHasKey('data', $content);
        self::assertEquals(['examples' => $this->data], $content['data']);

        // run persisted query using GET

        $response = $this->call('GET', '/graphql?extensions={"persistedQuery":{"version":1,"sha256Hash":"' . $hash . '"}}');

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->json();

        self::assertArrayHasKey('data', $content);
        self::assertEquals(['examples' => $this->data], $content['data']);
    }

    // This test demonstrates we don't actually check the 'version'
    public function testPersistedQueryFoundApqVersionIsNotChecked(): void
    {
        // run query and persist

        $response = $this->call('GET', '/graphql', [
            'query' => trim($this->queries['examples']),
            'extensions' => [
                'persistedQuery' => [
                    'version' => 3,
                    'sha256Hash' => hash('sha256', trim($this->queries['examples'])),
                ],
            ],
        ]);

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->json();

        self::assertArrayHasKey('data', $content);
        self::assertEquals(['examples' => $this->data], $content['data']);

        // run persisted query

        $response = $this->call('GET', '/graphql', [
            'extensions' => [
                'persistedQuery' => [
                    'version' => 9,
                    'sha256Hash' => hash('sha256', trim($this->queries['examples'])),
                ],
            ],
        ]);

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->json();

        self::assertArrayHasKey('data', $content);
        self::assertEquals(['examples' => $this->data], $content['data']);
    }

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

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->json();

        unset($content['errors'][0]['extensions']['file']);
        unset($content['errors'][0]['extensions']['line']);

        self::assertEquals([
            'errors' => [
                [
                    'message' => AutomaticPersistedQueriesError::MESSAGE_INVALID_HASH,
                    'extensions' => [
                        'code' => AutomaticPersistedQueriesError::CODE_INTERNAL_SERVER_ERROR,
                        'category' => AutomaticPersistedQueriesError::CATEGORY_APQ,
                    ],
                ],
            ],
        ], $content);
    }

    public function testPersistedQueryBatchingNotSupported(): void
    {
        $this->app['config']->set(['graphql.apq.enable' => false]);
        $this->app['config']->set('graphql.batching.enable', true);

        $response = $this->call('POST', '/graphql', [
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

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->json();

        unset($content[0]['errors'][0]['extensions']['file']);
        unset($content[0]['errors'][0]['extensions']['line']);
        unset($content[1]['errors'][0]['extensions']['file']);
        unset($content[1]['errors'][0]['extensions']['line']);

        self::assertArrayHasKey(0, $content);
        self::assertArrayHasKey(1, $content);

        self::assertEquals([
            [
                'message' => AutomaticPersistedQueriesError::MESSAGE_PERSISTED_QUERY_NOT_SUPPORTED,
                'extensions' => [
                    'code' => AutomaticPersistedQueriesError::CODE_PERSISTED_QUERY_NOT_SUPPORTED,
                    'category' => AutomaticPersistedQueriesError::CATEGORY_APQ,
                ],
            ],
        ], $content[0]['errors']);

        self::assertEquals([
            [
                'message' => AutomaticPersistedQueriesError::MESSAGE_PERSISTED_QUERY_NOT_SUPPORTED,
                'extensions' => [
                    'code' => AutomaticPersistedQueriesError::CODE_PERSISTED_QUERY_NOT_SUPPORTED,
                    'category' => AutomaticPersistedQueriesError::CATEGORY_APQ,
                ],
            ],
        ], $content[1]['errors']);
    }

    public function testPersistedQueryBatchingFoundNotFoundAndInvalidHash(): void
    {
        $this->app['config']->set('graphql.batching.enable', true);

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

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->json();

        self::assertArrayHasKey('data', $content);
        self::assertEquals(['examples' => $this->data], $content['data']);

        // run query persisted and not

        $response = $this->call('POST', '/graphql', [
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

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->json();

        unset($content[1]['errors'][0]['extensions']['file']);
        unset($content[1]['errors'][0]['extensions']['line']);
        unset($content[2]['errors'][0]['extensions']['file']);
        unset($content[2]['errors'][0]['extensions']['line']);

        self::assertArrayHasKey(0, $content);
        self::assertArrayHasKey(1, $content);
        self::assertArrayHasKey(2, $content);

        self::assertArrayHasKey('data', $content[0]);
        self::assertEquals(['examples' => $this->data], $content[0]['data']);

        self::assertEquals([
            [
                'message' => AutomaticPersistedQueriesError::MESSAGE_PERSISTED_QUERY_NOT_FOUND,
                'extensions' => [
                    'code' => AutomaticPersistedQueriesError::CODE_PERSISTED_QUERY_NOT_FOUND,
                    'category' => AutomaticPersistedQueriesError::CATEGORY_APQ,
                ],
            ],
        ], $content[1]['errors']);

        self::assertEquals([
            [
                'message' => AutomaticPersistedQueriesError::MESSAGE_INVALID_HASH,
                'extensions' => [
                    'code' => AutomaticPersistedQueriesError::CODE_INTERNAL_SERVER_ERROR,
                    'category' => AutomaticPersistedQueriesError::CATEGORY_APQ,
                ],
            ],
        ], $content[2]['errors']);
    }

    public function testPersistedQueryFoundWithUpload(): void
    {
        $query = 'mutation($file: Upload!) { uploadSingleFile(file: $file) }';
        $fileToUpload = UploadedFile::fake()->create('file.txt');
        $fileContent = "This is the\nuploaded\ndata";
        \Safe\fwrite($fileToUpload->tempFile, $fileContent);

        // run query and persist

        $response = $this->call(
            'POST',
            '/graphql',
            [
                'operations' => \Safe\json_encode([
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
                'map' => \Safe\json_encode([
                    '0' => ['variables.file'],
                ]),
            ],
            [],
            ['0' => $fileToUpload],
            ['CONTENT_TYPE' => 'multipart/form-data'],
        );

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->json();

        self::assertArrayHasKey('data', $content);
        self::assertEquals(['uploadSingleFile' => $fileContent], $content['data']);

        // run persisted query

        $response = $this->call(
            'POST',
            '/graphql',
            [
                'operations' => \Safe\json_encode([
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
                'map' => \Safe\json_encode([
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
            ['CONTENT_TYPE' => 'multipart/form-data'],
        );

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->json();

        self::assertArrayHasKey('data', $content);
        self::assertEquals(['uploadSingleFile' => $fileContent], $content['data']);
    }

    public function testPersistedQueryNotAnArray(): void
    {
        $this->app['config']->set(['graphql.apq.enable' => true]);

        $response = $this->call('GET', '/graphql', [
            'extensions' => [
                'persistedQuery' => 'invalid',
            ],
        ]);

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->json();

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

    public function testPersistedQueryParseError(): void
    {
        $query = '{ parse(error) }';

        $response = $this->call('GET', '/graphql', [
            'query' => $query,
            'extensions' => [
                'persistedQuery' => [
                    'version' => 1,
                    'sha256Hash' => hash('sha256', $query),
                ],
            ],
        ]);

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->json();

        unset($content['errors'][0]['extensions']['file']);
        unset($content['errors'][0]['extensions']['line']);

        $expected = [
            'errors' => [
                [
                    'message' => 'Syntax Error: Expected :, found )',
                    'extensions' => [
                    ],
                    'locations' => [
                        [
                            'line' => 1,
                            'column' => 14,
                        ],
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $content);
    }
}
