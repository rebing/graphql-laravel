<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database;

use Rebing\GraphQL\Tests\TestCaseDatabase;

class EmptyQueryTest extends TestCaseDatabase
{
    /**
     * @dataProvider dataForEmptyQuery
     * @param bool $isBatchRequest
     * @param array<mixed> $parameters
     */
    public function testEmptyQuery(bool $isBatchRequest, array $parameters): void
    {
        $response = $this->call('GET', '/graphql', $parameters);

        $this->assertSame(200, $response->getStatusCode());
        $results = $response->getData(true);

        if (false === $isBatchRequest) {
            $results = [$results];
        }

        $this->assertTrue(count($results) > 0);

        foreach ($results as $result) {
            $this->assertCount(1, $result['errors']);
            $this->assertSame('Syntax Error: Unexpected <EOF>', $result['errors'][0]['message']);
            $this->assertSame('graphql', $result['errors'][0]['extensions']['category']);
        }
    }

    /**
     * @return array<mixed>
     */
    public function dataForEmptyQuery(): array
    {
        return [
            // single request which is completely empty
            [
                false, [],
            ],
            // single request with an empty query parameter
            [
                false, ['query' => null],
            ],
            [
                false, ['query' => ''],
            ],
            [
                false, ['query' => ' '],
            ],
            [
                false, ['query' => '#'],
            ],
            // batch request with one completely empty batch, and batches with an empty query parameter
            [
                true, [[], ['query' => null], ['query' => ''], ['query' => ' '], ['query' => '#']],
            ],
        ];
    }
}
