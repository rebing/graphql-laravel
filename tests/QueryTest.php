<?php

declare(strict_types=1);

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
