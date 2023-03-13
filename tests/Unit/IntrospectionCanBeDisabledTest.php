<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit;

use GraphQL\Type\Introspection;
use Rebing\GraphQL\Tests\TestCase;

class IntrospectionCanBeDisabledTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('graphql.security.disable_introspection', true);
        $app['config']->set('graphql.security.query_max_depth', 11);
        $app['config']->set('app.debug', false);
    }

    public function testIntrospectionCanBeDisabled(): void
    {
        $query = Introspection::getIntrospectionQuery();

        $result = $this->httpGraphql($query, [
            'expectErrors' => true,
        ]);

        $expectedResult = [
            'errors' => [
                [
                    'message' => 'GraphQL introspection is not allowed, but the query contained __schema or __type',
                    'locations' => [
                        [
                            'line' => 2,
                            'column' => 5,
                        ],
                    ],
                ],
            ],
        ];
        self::assertSame($expectedResult, $result);
    }
}
