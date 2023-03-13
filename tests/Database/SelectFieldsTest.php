<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database;

use Illuminate\Support\Carbon;
use Rebing\GraphQL\Tests\Support\Models\Comment;
use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\Support\Queries\PostNonNullWithSelectFieldsAndModelQuery;
use Rebing\GraphQL\Tests\Support\Queries\PostQuery;
use Rebing\GraphQL\Tests\Support\Queries\PostQueryWithNonInjectableTypehintsQuery;
use Rebing\GraphQL\Tests\Support\Queries\PostQueryWithSelectFieldsClassInjectionQuery;
use Rebing\GraphQL\Tests\Support\Queries\PostsListOfWithSelectFieldsAndModelQuery;
use Rebing\GraphQL\Tests\Support\Queries\PostsNonNullAndListAndNonNullOfWithSelectFieldsAndModelQuery;
use Rebing\GraphQL\Tests\Support\Queries\PostsNonNullAndListOfWithSelectFieldsAndModelQuery;
use Rebing\GraphQL\Tests\Support\Queries\PostWithSelectFieldsAndModelAndAliasAndCustomResolverQuery;
use Rebing\GraphQL\Tests\Support\Queries\PostWithSelectFieldsAndModelAndAliasCallbackQuery;
use Rebing\GraphQL\Tests\Support\Queries\PostWithSelectFieldsAndModelAndAliasQuery;
use Rebing\GraphQL\Tests\Support\Queries\PostWithSelectFieldsAndModelQuery;
use Rebing\GraphQL\Tests\Support\Queries\PostWithSelectFieldsNoModelQuery;
use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;
use Rebing\GraphQL\Tests\Support\Types\PostType;
use Rebing\GraphQL\Tests\Support\Types\PostWithModelAndAliasAndCustomResolverType;
use Rebing\GraphQL\Tests\Support\Types\PostWithModelAndAliasType;
use Rebing\GraphQL\Tests\Support\Types\PostWithModelType;
use Rebing\GraphQL\Tests\TestCaseDatabase;

class SelectFieldsTest extends TestCaseDatabase
{
    use SqlAssertionTrait;

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'query' => [
                PostNonNullWithSelectFieldsAndModelQuery::class,
                PostQuery::class,
                PostsListOfWithSelectFieldsAndModelQuery::class,
                PostsNonNullAndListAndNonNullOfWithSelectFieldsAndModelQuery::class,
                PostsNonNullAndListOfWithSelectFieldsAndModelQuery::class,
                PostWithSelectFieldsAndModelAndAliasAndCustomResolverQuery::class,
                PostWithSelectFieldsAndModelAndAliasQuery::class,
                PostWithSelectFieldsAndModelQuery::class,
                PostWithSelectFieldsNoModelQuery::class,
                PostWithSelectFieldsAndModelAndAliasCallbackQuery::class,
                PostQueryWithSelectFieldsClassInjectionQuery::class,
                PostQueryWithNonInjectableTypehintsQuery::class,
            ],
        ]);

        $app['config']->set('graphql.types', [
            PostType::class,
            PostWithModelAndAliasAndCustomResolverType::class,
            PostWithModelAndAliasType::class,
            PostWithModelType::class,
        ]);

        $app['config']->set('app.debug', true);
    }

    public function testWithoutSelectFields(): void
    {
        /** @var Post $post */
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

        $this->assertSqlQueries(
            <<<'SQL'
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

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($expectedResult, $response->json());
    }

    public function testWithSelectFieldsClassInjection(): void
    {
        /** @var Post $post */
        $post = factory(Post::class)->create([
            'title' => 'Title of the post',
        ]);

        $graphql = <<<GRAQPHQL
{
  postWithSelectFieldClassInjection(id: $post->id) {
    id
    title
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $response = $this->call('GET', '/graphql', [
            'query' => $graphql,
        ]);

        $this->assertSqlQueries(
            <<<'SQL'
select "id", "title" from "posts" where "posts"."id" = ? limit 1;
SQL
        );

        $expectedResult = [
            'data' => [
                'postWithSelectFieldClassInjection' => [
                    'id' => "$post->id",
                    'title' => 'Title of the post',
                ],
            ],
        ];

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($expectedResult, $response->json());
    }

    public function testWithSelectFieldsNonInjectableTypehints(): void
    {
        /** @var Post $post */
        $post = factory(Post::class)->create([
            'title' => 'Title of the post',
        ]);

        $graphql = <<<GRAQPHQL
{
  postQueryWithNonInjectableTypehints(id: $post->id) {
    id
    title
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($graphql, [
            'expectErrors' => true,
        ]);

        unset($result['errors'][0]['extensions']['file']);
        unset($result['errors'][0]['extensions']['line']);
        unset($result['errors'][0]['extensions']['trace']);

        $expectedResult = [
            'errors' => [
                [
                    'message' => 'Internal server error',
                    'extensions' => [
                        'debugMessage' => "'coolNumber' could not be injected",
                    ],
                    'locations' => [
                        [
                            'line' => 2,
                            'column' => 3,
                        ],
                    ],
                    'path' => [
                        'postQueryWithNonInjectableTypehints',
                    ],
                ],
            ],
            'data' => [
                'postQueryWithNonInjectableTypehints' => null,
            ],
        ];
        self::assertEquals($expectedResult, $result);
    }

    public function testWithSelectFieldsAndModel(): void
    {
        /** @var Post $post */
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

        $this->assertSqlQueries(
            <<<'SQL'
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

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($expectedResult, $response->json());
    }

    public function testWithNonNullSelectFieldsAndModel(): void
    {
        /** @var Post $post */
        $post = factory(Post::class)->create([
            'title' => 'Title of the post',
        ]);

        $graphql = <<<GRAQPHQL
{
  postNonNullWithSelectFieldsAndModel(id: $post->id) {
    id
    title
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $response = $this->call('GET', '/graphql', [
            'query' => $graphql,
        ]);

        $expectedResult = [
            'data' => [
                'postNonNullWithSelectFieldsAndModel' => [
                    'id' => "$post->id",
                    'title' => 'Title of the post',
                ],
            ],
        ];

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($expectedResult, $response->json());
    }

    public function testWithListOfSelectFieldsAndModel(): void
    {
        /** @var Post $post */
        $post = factory(Post::class)->create([
            'title' => 'Title of the post',
        ]);

        $graphql = <<<'GRAQPHQL'
{
  postsListOfWithSelectFieldsAndModel {
    id
    title
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $response = $this->call('GET', '/graphql', [
            'query' => $graphql,
        ]);

        $this->assertSqlQueries('select "posts"."id", "posts"."title" from "posts";');

        $expectedResult = [
            'data' => [
                'postsListOfWithSelectFieldsAndModel' => [
                    [
                        'id' => "$post->id",
                        'title' => 'Title of the post',
                    ],
                ],
            ],
        ];

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($expectedResult, $response->json());
    }

    public function testWithListOfSelectFieldsAndModelWithSameFieldsInFragment(): void
    {
        /** @var Post $post */
        $post = factory(Post::class)->create([
            'title' => 'Title of the post',
        ]);

        $graphql = <<<'GRAQPHQL'
{
  postsListOfWithSelectFieldsAndModel {
    id
    title
    ...Base
    ...Base2
  }
}

fragment Base on PostWithModel {
    id
}

fragment Base2 on PostWithModel {
    id
    title
}
GRAQPHQL;

        $this->sqlCounterReset();

        $response = $this->httpGraphql($graphql);

        $this->assertSqlQueries('select "posts"."id", "posts"."title" from "posts";');

        $expectedResult = [
            'data' => [
                'postsListOfWithSelectFieldsAndModel' => [
                    [
                        'id' => "$post->id",
                        'title' => 'Title of the post',
                    ],
                ],
            ],
        ];

        self::assertEquals($expectedResult, $response);
    }

    public function testWithNonNullAndListOfSelectFieldsAndModel(): void
    {
        /** @var Post $post */
        $post = factory(Post::class)->create([
            'title' => 'Title of the post',
        ]);

        $graphql = <<<'GRAQPHQL'
{
  postsNonNullAndListOfWithSelectFieldsAndModel {
    id
    title
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $response = $this->call('GET', '/graphql', [
            'query' => $graphql,
        ]);

        $expectedResult = [
            'data' => [
                'postsNonNullAndListOfWithSelectFieldsAndModel' => [
                    [
                        'id' => "$post->id",
                        'title' => 'Title of the post',
                    ],
                ],
            ],
        ];

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($expectedResult, $response->json());
    }

    public function testWithNonNullAndListOfAndNonNullSelectFieldsAndModel(): void
    {
        /** @var Post $post */
        $post = factory(Post::class)->create([
            'title' => 'Title of the post',
        ]);

        $graphql = <<<'GRAQPHQL'
{
  postsNonNullAndListAndNonNullOfWithSelectFieldsAndModel {
    id
    title
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $response = $this->call('GET', '/graphql', [
            'query' => $graphql,
        ]);

        $expectedResult = [
            'data' => [
                'postsNonNullAndListAndNonNullOfWithSelectFieldsAndModel' => [
                    [
                        'id' => "$post->id",
                        'title' => 'Title of the post',
                    ],
                ],
            ],
        ];

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($expectedResult, $response->json());
    }

    public function testWithSelectFieldsAndModelAndAlias(): void
    {
        /** @var Post $post */
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

        $this->assertSqlQueries(
            <<<'SQL'
select "posts"."id", "posts"."title" from "posts" where "posts"."id" = ? limit 1;
SQL
        );

        self::assertEquals(200, $response->getStatusCode());

        $expectedResult = [
            'data' => [
                'postWithSelectFieldsAndModelAndAlias' => [
                    'id' => '1',
                    'description' => 'Description of the post',
                ],
            ],
        ];

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($expectedResult, $response->json());
    }

    public function testWithSelectFieldsAndModelAndCallbackSqlAlias(): void
    {
        /** @var Post $post */
        $post = factory(Post::class)->create([
            'title' => 'Description of the post',
        ]);

        Carbon::setTestNow('2018-01-01');

        factory(Comment::class)
            ->create([
                'post_id' => $post->id,
                'created_at' => new Carbon('2000-01-01'),
            ]);

        factory(Comment::class)
            ->create([
                'post_id' => $post->id,
                'created_at' => new Carbon('2018-05-05'),
            ]);

        $graphql = <<<GRAQPHQL
    {
      postWithSelectFieldsAndModelAndAliasCallback(id: $post->id) {
        id
        description
        commentsLastMonth
      }
    }
GRAQPHQL;

        $this->sqlCounterReset();

        $response = $this->call('GET', '/graphql', [
            'query' => $graphql,
        ]);

        $this->assertSqlQueries(
            <<<'SQL'
select "posts"."id", "posts"."title", (SELECT count(*) FROM comments WHERE posts.id = comments.post_id AND DATE(created_at) > '2018-01-01 00:00:00') AS commentsLastMonth from "posts" where "posts"."id" = ? limit 1;
SQL
        );

        self::assertEquals(200, $response->getStatusCode());

        $expectedResult = [
            'data' => [
                'postWithSelectFieldsAndModelAndAliasCallback' => [
                    'id' => '1',
                    'description' => 'Description of the post',
                    'commentsLastMonth' => 1,
                ],
            ],
        ];

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($expectedResult, $response->json());
    }

    public function testWithSelectFieldsAndModelAndAliasAndCustomResolver(): void
    {
        /** @var Post $post */
        $post = factory(Post::class)->create([
            'title' => 'Description of the post',
        ]);

        $graphql = <<<GRAQPHQL
{
  postWithSelectFieldsAndModelAndAliasAndCustomResolver(id: $post->id) {
    id
    description
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $response = $this->call('GET', '/graphql', [
            'query' => $graphql,
        ]);

        $this->assertSqlQueries(
            <<<'SQL'
select "posts"."id", "posts"."title" from "posts" where "posts"."id" = ? limit 1;
SQL
        );

        self::assertEquals(200, $response->getStatusCode());

        $expectedResult = [
            'data' => [
                'postWithSelectFieldsAndModelAndAliasAndCustomResolver' => [
                    'id' => '1',
                    'description' => 'Custom resolver',
                ],
            ],
        ];

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($expectedResult, $response->json());
    }

    public function testWithSelectFieldsNoModel(): void
    {
        /** @var Post $post */
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

        $this->assertSqlQueries(
            <<<'SQL'
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

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($expectedResult, $response->json());
    }
}
