<?php

use Rebing\Support\Field;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;

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
            'Example' => ExampleType::class
        ]);
    }
}
