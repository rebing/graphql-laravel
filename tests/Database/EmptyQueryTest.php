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
    public function testEmptyQuery(array $parameters, string $expectedError): void
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
            static function (array $result): array {
                unset($result['errors'][0]['extensions']['file']);
                unset($result['errors'][0]['extensions']['line']);
                unset($result['errors'][0]['extensions']['trace']);

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
                        ],
                    ],
                ],
            ],
            [
                'errors' => [
                    [
                        'message' => 'GraphQL Request must include at least one of those two parameters: "query" or "queryId"',
                        'extensions' => [
                        ],
                    ],
                ],
            ],
            [
                'errors' => [
                    [
                        'message' => 'GraphQL Request must include at least one of those two parameters: "query" or "queryId"',
                        'extensions' => [
                        ],
                    ],
                ],
            ],
            [
                'errors' => [
                    [
                        'message' => 'GraphQL Request must include at least one of those two parameters: "query" or "queryId"',
                        'extensions' => [
                        ],
                    ],
                ],
            ],
            [
                'errors' => [
                    [
                        'message' => 'Syntax Error: Unexpected <EOF>',
                        'extensions' => [
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
                'expectedError' => 'GraphQL Request must include at least one of those two parameters: "query" or "queryId"',
            ],
            // single request with an empty query parameter
            [
                'parameters' => ['query' => null],
                'expectedError' => 'GraphQL Request must include at least one of those two parameters: "query" or "queryId"',
            ],
            [
                'parameters' => ['query' => ''],
                'expectedError' => 'GraphQL Request must include at least one of those two parameters: "query" or "queryId"',
            ],
            [
                'parameters' => ['query' => ' '],
                'expectedError' => 'GraphQL Request must include at least one of those two parameters: "query" or "queryId"',
            ],
            [
                'parameters' => ['query' => '#'],
                'expectedError' => 'Syntax Error: Unexpected <EOF>',
            ],
        ];
    }
}
