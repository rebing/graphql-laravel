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
     * @param bool $isExpectedCleanResult
     */
    public function testEmptyQuery(array $parameters, bool $isBatchRequest, bool $isExpectedCleanResult): void
    {
        $response = $this->call('GET', '/graphql', $parameters);

        $this->assertSame(200, $response->getStatusCode());
        $results = $isBatchRequest ? $response->getData(true) : [$response->getData(true)];

        foreach ($results as $result) {
            if ($isExpectedCleanResult) {
                $this->assertArrayNotHasKey('errors', $result);
            } else {
                $this->assertCount(1, $result['errors']);
                $this->assertSame('Syntax Error: Unexpected <EOF>', $result['errors'][0]['message']);
                $this->assertSame('graphql', $result['errors'][0]['extensions']['category']);
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
                [], false, true,
            ],
            // single request with an empty query parameter
            [
                ['query' => null], false, false,
            ],
            [
                ['query' => ''], false, false,
            ],
            [
                ['query' => ' '], false, false,
            ],
            [
                ['query' => '#'], false, false,
            ],
            // batch request with one completely empty batch, and batches with an empty query parameter
            [
                [[], ['query' => null], ['query' => ''], ['query' => ' '], ['query' => '#']], true, false,
            ],
        ];
    }
}
