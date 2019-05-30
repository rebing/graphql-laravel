<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database;

use Rebing\GraphQL\Tests\TestCaseDatabase;
use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\Support\Types\PostWithModelType;
use Rebing\GraphQL\Tests\Support\Queries\PostQuery;
use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;
use Rebing\GraphQL\Tests\Support\Queries\PostWithSelectFieldsQuery;

class SelectFieldsTest extends TestCaseDatabase
{
    use SqlAssertionTrait;

    public function testWithoutSelectFields(): void
    {
        $post = factory(Post::class)->create([
            'title' => 'Title of the post',
        ]);

        $graphql = <<<GRAQPHQL
{
  post(id: $post->id) {
    id
    title
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $response = $this->call('GET', '/graphql', [
            'query' => $graphql,
        ]);

        $this->assertSqlQueries(<<<'SQL'
select * from "posts" where "posts"."id" = ? limit 1;
SQL
        );

        $expectedResult = [
            'data' => [
                'post' => [
                    'id' => '1',
                    'title' => 'Title of the post',
                ],
            ],
        ];

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($expectedResult, $response->json());
    }

    public function testWithSelectFields(): void
    {
        $post = factory(Post::class)->create([
            'title' => 'Title of the post',
        ]);

        $graphql = <<<GRAQPHQL
{
  postWithSelectFields(id: $post->id) {
    id
    title
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $response = $this->call('GET', '/graphql', [
            'query' => $graphql,
        ]);

        $this->assertSqlQueries(<<<'SQL'
select "posts"."id", "posts"."title" from "posts" where "posts"."id" = ? limit 1;
SQL
        );

        $expectedResult = [
            'data' => [
                'postWithSelectFields' => [
                    'id' => '1',
                    'title' => 'Title of the post',
                ],
            ],
        ];

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($expectedResult, $response->json());
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'query' => [
                PostQuery::class,
                PostWithSelectFieldsQuery::class,
            ],
        ]);

        $app['config']->set('graphql.schemas.custom', null);

        $app['config']->set('graphql.types', [
            PostWithModelType::class,
        ]);

        $app['config']->set('app.debug', true);
    }
}
