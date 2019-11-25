<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit;

use Rebing\GraphQL\Tests\Support\Objects\ExamplesQuery;
use Rebing\GraphQL\Tests\Support\Objects\ExampleType;

class QueryTest extends FieldTest
{
    protected function getFieldClass()
    {
        return ExamplesQuery::class;
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.types', [
            'Example' => ExampleType::class,
        ]);
    }
}
