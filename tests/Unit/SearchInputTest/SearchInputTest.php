<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\SearchInputTest;

use PHPUnit\Framework\Attributes\DataProvider;
use Rebing\GraphQL\Tests\TestCase;

/**
 * @description TestCase for @oneOf directive
 * @see https://github.com/rebing/graphql-laravel/pull/1212
 */
class SearchInputTest extends TestCase
{
    /**
     * @return list<array{searchParameter: string, expectedResponse: array<string, mixed>}>
     */
    public static function dataForValidOneOfInput(): array
    {
        return [
            [
                'searchParameter' => 'byId: 123',
                'expectedResponse' => ['data' => ['user' => 'byId']],
            ],

            [
                'searchParameter' => 'byEmail: "user@example.com"',
                'expectedResponse' => ['data' => ['user' => 'byEmail']],
            ],

            [
                'searchParameter' => 'byUsername: "ExampleUser"',
                'expectedResponse' => ['data' => ['user' => 'byUsername']],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $expectedResponse
     */
    #[DataProvider('dataForValidOneOfInput')]
    public function testValidOneOfInput(string $searchParameter, array $expectedResponse): void
    {
        $query = \sprintf(
            <<<'GRAPHQL'
query {
    user(
        search: {
            %s
        }
    )
}
GRAPHQL,
            $searchParameter,
        );

        $result = $this->httpGraphql($query, [
            'expectErrors' => false,
        ]);

        self::assertEquals($expectedResponse, $result);
    }

    public function testInvalidOneOfInput(): void
    {
        $query = <<<'GRAPHQL'
query {
    user(
        search: {
            byId: 123
            byUsername: "ExampleUser"
        }
    )
}
GRAPHQL;

        $result = $this->httpGraphql($query, [
            'expectErrors' => true,
        ]);

        $expected = [
            'errors' => [
                [
                    'message' => "OneOf input object 'SearchInput' must specify exactly one field, but 2 fields were provided.",
                    'locations' => [
                        [
                            'line' => 3,
                            'column' => 17,
                        ],
                    ],
                    'extensions' => [],
                ],
            ],
        ];

        self::assertEquals($expected, $result);
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'query' => [
                UserQuery::class,
            ],
            'types' => [
                SearchInput::class,
            ],
        ]);
    }
}
