<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit;

use Rebing\GraphQL\Tests\TestCase;

class EndpointTest extends TestCase
{
    /**
     * Test get with default schema.
     */
    public function testGetDefault(): void
    {
        $response = $this->call('GET', '/graphql', [
            'query' => $this->queries['examples'],
        ]);

        self::assertEquals($response->getStatusCode(), 200);

        $content = $response->getData(true);
        self::assertArrayHasKey('data', $content);
        self::assertEquals($content['data'], [
            'examples' => $this->data,
        ]);
    }

    /**
     * Test get with custom schema.
     */
    public function testGetCustom(): void
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

    /**
     * Test get with variables.
     */
    public function testGetWithVariables(): void
    {
        $response = $this->call('GET', '/graphql', [
            'query' => $this->queries['examplesWithVariables'],
            'variables' => [
                'index' => 0,
            ],
        ]);

        self::assertEquals($response->getStatusCode(), 200);

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

        self::assertEquals($response->getStatusCode(), 200);

        $content = $response->getData(true);
        self::assertArrayHasKey('data', $content);
        self::assertEquals($content['data'], [
            'examples' => [
                $this->data[0],
            ],
        ]);
    }

    /**
     * Test get with unauthorized query.
     */
    public function testGetUnauthorized(): void
    {
        $response = $this->call('GET', '/graphql', [
            'query' => $this->queries['examplesWithAuthorize'],
        ]);

        self::assertEquals($response->getStatusCode(), 200);

        $content = $response->getData(true);
        self::assertArrayHasKey('data', $content);
        self::assertArrayHasKey('errors', $content);
        self::assertEquals($content['errors'][0]['message'], 'Unauthorized');
        self::assertNull($content['data']['examplesAuthorize']);
    }

    /**
     * Test get with unauthorized query and custom error message.
     */
    public function testGetUnauthorizedWithCustomError(): void
    {
        $response = $this->call('GET', '/graphql', [
            'query' => $this->queries['examplesWithAuthorizeMessage'],
        ]);

        self::assertEquals($response->getStatusCode(), 200);

        $content = $response->getData(true);
        self::assertArrayHasKey('data', $content);
        self::assertArrayHasKey('errors', $content);
        self::assertEquals($content['errors'][0]['message'], 'You are not authorized to perform this action');
        self::assertNull($content['data']['examplesAuthorizeMessage']);
    }

    /**
     * Test support batched queries.
     */
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

        self::assertEquals($response->getStatusCode(), 200);

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
