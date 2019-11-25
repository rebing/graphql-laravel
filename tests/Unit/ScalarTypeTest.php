<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit;

use Rebing\GraphQL\Tests\Support\Queries\ReturnScalarQuery;
use Rebing\GraphQL\Tests\Support\Types\MyCustomScalarString;
use Rebing\GraphQL\Tests\TestCase;

class ScalarTypeTest extends TestCase
{
    public function testScalarType(): void
    {
        $query = <<<'GRAPHQL'
{
    returnScalar
}
GRAPHQL;

        $result = $this->graphql($query);

        $expectedResult = [
            'data' => [
                'returnScalar' => 'just a string',
            ],
        ];
        $this->assertSame($expectedResult, $result);
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'query' => [
                ReturnScalarQuery::class,
            ],
        ]);

        $app['config']->set('graphql.schemas.custom', null);

        $app['config']->set('graphql.types', [
            MyCustomScalarString::class,
        ]);
    }
}
