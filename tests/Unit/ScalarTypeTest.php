<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit;

use Error;
use Rebing\GraphQL\Tests\TestCase;
use Rebing\GraphQL\Tests\Support\Queries\ReturnScalarQuery;
use Rebing\GraphQL\Tests\Support\Types\MyCustomScalarString;

class ScalarTypeTest extends TestCase
{
    public function testScalarType(): void
    {
        $query = <<<'GRAPHQL'
{
    returnScalar
}
GRAPHQL;

        $this->expectException(Error::class);
        $this->expectExceptionMessage('Call to undefined method Rebing\GraphQL\Tests\Support\Types\MyCustomScalarString::toType()');

        $this->graphql($query);
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
