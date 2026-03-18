<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database;

use PHPUnit\Framework\Attributes\DataProvider;
use Rebing\GraphQL\Tests\TestCaseDatabase;

/**
 * Tests for empty query handling when GET is explicitly enabled.
 *
 * These tests ensure empty GET requests are handled correctly when users
 * opt in to GET support via the schema 'method' config. The default
 * config is POST-only, but GET can be enabled explicitly.
 */
class EmptyQueryGetTest extends TestCaseDatabase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default.method', ['GET', 'POST']);
    }

    /**
     * @param list<mixed> $parameters
     */
    #[DataProvider('dataForEmptyGetQuery')]
    public function testEmptyGetQuery(array $parameters, string $expectedError): void
    {
        $response = $this->call('GET', '/graphql', $parameters);

        self::assertSame(200, $response->getStatusCode());
        $result = $response->getData(true);

        self::assertCount(1, $result['errors']);
        self::assertSame($expectedError, $result['errors'][0]['message']);
    }

    /**
     * @return list<mixed>
     */
    public static function dataForEmptyGetQuery(): array
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
