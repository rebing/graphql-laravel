<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database;

use Rebing\GraphQL\Tests\TestCaseDatabase;

class EmptyQueryTest extends TestCaseDatabase
{
    /**
     * @dataProvider dataForEmptyQuery
     * @param array<mixed> $parameters
     */
    public function testEmptyQuery(array $parameters, bool $isBatchRequest, string $expectedError): void
    {
        $response = $this->call('GET', '/graphql', $parameters);

        self::assertSame(200, $response->getStatusCode());
        $result = $response->getData(true);

        self::assertCount(1, $result['errors']);
        self::assertSame($expectedError, $result['errors'][0]['message']);
    }

    public function testEmptyBatchedQuery(): void
    {
        $response = $this->call('POST', '/graphql', [
            [],
            ['query' => null],
            ['query' => ''],
            ['query' => ' '],
            ['query' => '#'],
        ]);

        self::assertSame(200, $response->getStatusCode());
        $results = $response->getData(true);

        $results = array_map(
            function (array $result): array {
                unset($result['errors'][0]['trace']);

                return $result;
            },
            $results
        );

        $expected = [
            [
                'errors' => [
                    [
                        'message' => 'GraphQL Request must include at least one of those two parameters: "query" or "queryId"',
                        'extensions' => [
                            'category' => 'request',
                        ],
                    ],
                ],
            ],
            [
                'errors' => [
                    [
                        'message' => 'GraphQL Request must include at least one of those two parameters: "query" or "queryId"',
                        'extensions' => [
                            'category' => 'request',
                        ],
                    ],
                ],
            ],
            [
                'errors' => [
                    [
                        'message' => 'GraphQL Request must include at least one of those two parameters: "query" or "queryId"',
                        'extensions' => [
                            'category' => 'request',
                        ],
                    ],
                ],
            ],
            [
                'errors' => [
                    [
                        'message' => 'GraphQL Request must include at least one of those two parameters: "query" or "queryId"',
                        'extensions' => [
                            'category' => 'request',
                        ],
                    ],
                ],
            ],
            [
                'errors' => [
                    [
                        'message' => 'Syntax Error: Unexpected <EOF>',
                        'extensions' => [
                            'category' => 'graphql',
                        ],
                        'locations' => [
                            [
                                'line' => 1,
                                'column' => 2,
                            ],
                        ],
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $results);
    }

    /**
     * @return array<mixed>
     */
    public function dataForEmptyQuery(): array
    {
        return [
            // completely empty request
            [
                'parameters' => [],
                'isBatchRequest' => false,
                'expectedError' => 'GraphQL Request must include at least one of those two parameters: "query" or "queryId"',
            ],
            // single request with an empty query parameter
            [
                'parameters' => ['query' => null],
                'isBatchRequest' => false,
                'expectedError' => 'GraphQL Request must include at least one of those two parameters: "query" or "queryId"',
            ],
            [
                'parameters' => ['query' => ''],
                'isBatchRequest' => false,
                'expectedError' => 'GraphQL Request must include at least one of those two parameters: "query" or "queryId"',
            ],
            [
                'parameters' => ['query' => ' '],
                'isBatchRequest' => false,
                'expectedError' => 'GraphQL Request must include at least one of those two parameters: "query" or "queryId"',
            ],
            [
                'parameters' => ['query' => '#'],
                'isBatchRequest' => false,
                'expectedError' => 'Syntax Error: Unexpected <EOF>',
            ],
        ];
    }
}
