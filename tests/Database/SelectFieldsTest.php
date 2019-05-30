<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database;

use Rebing\GraphQL\Tests\TestCaseDatabase;
use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\Support\Types\PostType;
use Rebing\GraphQL\Tests\Support\Queries\PostQuery;
use Rebing\GraphQL\Tests\Support\Types\PostWithModelType;
use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;
use Rebing\GraphQL\Tests\Support\Types\PostWithModelAndAliasType;
use Rebing\GraphQL\Tests\Support\Queries\PostWithSelectFieldsNoModelQuery;
use Rebing\GraphQL\Tests\Support\Queries\PostWithSelectFieldsAndModelQuery;
use Rebing\GraphQL\Tests\Support\Queries\PostWithSelectFieldsAndModelAndAliasQuery;

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
                    'id' => "$post->id",
                    'title' => 'Title of the post',
                ],
            ],
        ];

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($expectedResult, $response->json());
    }

    public function testWithSelectFieldsAndModel(): void
    {
        $post = factory(Post::class)->create([
            'title' => 'Title of the post',
        ]);

        $graphql = <<<GRAQPHQL
{
  postWithSelectFieldsAndModel(id: $post->id) {
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
                'postWithSelectFieldsAndModel' => [
                    'id' => "$post->id",
                    'title' => 'Title of the post',
                ],
            ],
        ];

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($expectedResult, $response->json());
    }

    public function testWithSelectFieldsAndModelAndAlias(): void
    {
        $post = factory(Post::class)->create([
            'title' => 'Description of the post',
        ]);

        $graphql = <<<GRAQPHQL
{
  postWithSelectFieldsAndModelAndAlias(id: $post->id) {
    id
    description
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

        $this->assertEquals($response->getStatusCode(), 200);

        $result = $response->json();

        $this->assertNull($result['data']['postWithSelectFieldsAndModelAndAlias']);
        $this->assertSame('Cannot return null for non-nullable field PostWithModelAndAlias.description.', $result['errors'][0]['debugMessage']);
    }

    public function testWithSelectFieldsNoModel(): void
    {
        $post = factory(Post::class)->create([
            'title' => 'Title of the post',
        ]);

        $graphql = <<<GRAQPHQL
{
  postWithSelectFieldsNoModel(id: $post->id) {
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
select "id", "title" from "posts" where "posts"."id" = ? limit 1;
SQL
        );

        $expectedResult = [
            'data' => [
                'postWithSelectFieldsNoModel' => [
                    'id' => "$post->id",
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
                PostWithSelectFieldsAndModelQuery::class,
                PostWithSelectFieldsAndModelAndAliasQuery::class,
                PostWithSelectFieldsNoModelQuery::class,
            ],
        ]);

        $app['config']->set('graphql.schemas.custom', null);

        $app['config']->set('graphql.types', [
            PostType::class,
            PostWithModelType::class,
            PostWithModelAndAliasType::class,
        ]);

        $app['config']->set('app.debug', true);
    }
}
