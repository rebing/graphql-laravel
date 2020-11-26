<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database;

use Rebing\GraphQL\Tests\TestCaseDatabase;

class EmptyQueryTest extends TestCaseDatabase
{
    /**
     * @dataProvider dataForEmptyQuery
     * @param array<mixed> $parameters
     * @param bool $isBatchRequest
     * @param bool $expectErrors
     */
    public function testEmptyQuery(array $parameters, bool $isBatchRequest, bool $expectErrors): void
    {
        $response = $this->call('GET', '/graphql', $parameters);

        $this->assertSame(200, $response->getStatusCode());
        $results = $isBatchRequest ? $response->getData(true) : [$response->getData(true)];

        foreach ($results as $result) {
            if ($expectErrors) {
                $this->assertCount(1, $result['errors']);
                $this->assertSame('Syntax Error: Unexpected <EOF>', $result['errors'][0]['message']);
                $this->assertSame('graphql', $result['errors'][0]['extensions']['category']);
            } else {
                $this->assertArrayNotHasKey('errors', $result);
            }
        }
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
                'expectErrors' => false,
            ],
            // single request with an empty query parameter
            [
                'parameters' => ['query' => null],
                'isBatchRequest' => false,
                'expectErrors' => true,
            ],
            [
                'parameters' => ['query' => ''],
                'isBatchRequest' => false,
                'expectErrors' => true,
            ],
            [
                'parameters' => ['query' => ' '],
                'isBatchRequest' => false,
                'expectErrors' => true,
            ],
            [
                'parameters' => ['query' => '#'],
                'isBatchRequest' => false,
                'expectErrors' => true,
            ],
            // batch request with one completely empty batch, and batches with an empty query parameter
            [
                'parameters' => [[], ['query' => null], ['query' => ''], ['query' => ' '], ['query' => '#']],
                'isBatchRequest' => true,
                'expectErrors' => true,
            ],
        ];
    }
}
