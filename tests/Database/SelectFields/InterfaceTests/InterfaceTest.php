<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\InterfaceTests;

use Rebing\GraphQL\Tests\TestCaseDatabase;
use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;

class InterfaceTest extends TestCaseDatabase
{
    use SqlAssertionTrait;

    public function testGeneratedSqlQuery(): void
    {
        factory(Post::class)->create([
            'title' => 'Title of the post',
        ]);

        $graphql = <<<'GRAQPHQL'
{
  exampleInterfaceQuery {
    title
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->graphql($graphql);

        $this->assertSqlQueries(<<<'SQL'
select "title" from "posts";
SQL
        );

        $expectedResult = [
            'data' => [
                'exampleInterfaceQuery' => [
                    [
                        'title' => 'Title of the post',
                    ],
                ],
            ],
        ];
        $this->assertSame($expectedResult, $result);
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'query' => [
                ExampleInterfaceQuery::class,
            ],
        ]);

        $app['config']->set('graphql.schemas.custom', null);

        $app['config']->set('graphql.types', [
            ExampleInterfaceType::class,
            InterfaceImpl1Type::class,
        ]);
    }
}
