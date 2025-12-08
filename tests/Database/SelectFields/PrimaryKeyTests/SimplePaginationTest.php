<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\PrimaryKeyTests;

use Rebing\GraphQL\Tests\Support\Models\Comment;
use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;
use Rebing\GraphQL\Tests\TestCaseDatabase;

class SimplePaginationTest extends TestCaseDatabase
{
    use SqlAssertionTrait;

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'query' => [
                PrimaryKeyQuery::class,
                PrimaryKeySimplePaginationQuery::class,
            ],
        ]);

        $app['config']->set('graphql.types', [
            CommentType::class,
            PostType::class,
        ]);
    }

    public function testSimplePagination(): void
    {
        /** @var Post $post */
        $post = Post::factory()->create([
            'title' => 'post 1',
        ]);
        Comment::factory()->create([
            'title' => 'post 1 comment 1',
            'post_id' => $post->id,
        ]);
        /** @var Post $post */
        $post = Post::factory()->create([
            'title' => 'post 2',
        ]);
        Comment::factory()->create([
            'title' => 'post 2 comment 1',
            'post_id' => $post->id,
        ]);

        $query = <<<'GRAQPHQL'
{
  primaryKeySimplePaginationQuery {
    current_page
    data {
      title
      comments {
        title
      }
    }
    from
    has_more_pages
    per_page
    to
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($query);

        $this->assertSqlQueries(
            <<<'SQL'
select "posts"."title", "posts"."id" from "posts" limit 2 offset 0;
select "comments"."title", "comments"."post_id", "comments"."id" from "comments" where "comments"."post_id" in (?, ?) order by "comments"."id" asc;
SQL,
        );

        $expectedResult = [
            'data' => [
                'primaryKeySimplePaginationQuery' => [
                    'current_page' => 1,
                    'data' => [
                        [
                            'title' => 'post 1',
                            'comments' => [
                                [
                                    'title' => 'post 1 comment 1',
                                ],
                            ],
                        ],
                    ],
                    'from' => 1,
                    'has_more_pages' => true,
                    'per_page' => 1,
                    'to' => 1,
                ],
            ],
        ];
        self::assertEquals($expectedResult, $result);
    }

    public function testSimplePaginationHasNoMorePage(): void
    {
        /** @var Post $post */
        $post = Post::factory()->create([
            'title' => 'post 1',
        ]);
        Comment::factory()->create([
            'title' => 'post 1 comment 1',
            'post_id' => $post->id,
        ]);
        $query = <<<'GRAQPHQL'
{
  primaryKeySimplePaginationQuery {
    current_page
    data {
      title
      comments {
        title
      }
    }
    from
    has_more_pages
    per_page
    to
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($query);

        $this->assertSqlQueries(
            <<<'SQL'
select "posts"."title", "posts"."id" from "posts" limit 2 offset 0;
select "comments"."title", "comments"."post_id", "comments"."id" from "comments" where "comments"."post_id" in (?) order by "comments"."id" asc;
SQL,
        );

        $expectedResult = [
            'data' => [
                'primaryKeySimplePaginationQuery' => [
                    'current_page' => 1,
                    'data' => [
                        [
                            'title' => 'post 1',
                            'comments' => [
                                [
                                    'title' => 'post 1 comment 1',
                                ],
                            ],
                        ],
                    ],
                    'from' => 1,
                    'has_more_pages' => false,
                    'per_page' => 1,
                    'to' => 1,
                ],
            ],
        ];
        self::assertEquals($expectedResult, $result);
    }

    public function testSimplePaginationReturnEmptyList(): void
    {
        $query = <<<'GRAQPHQL'
{
  primaryKeySimplePaginationQuery {
    current_page
    data {
      title
      comments {
        title
      }
    }
    from
    has_more_pages
    per_page
    to
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($query);

        $this->assertSqlQueries(
            <<<'SQL'
select "posts"."title", "posts"."id" from "posts" limit 2 offset 0;
SQL,
        );

        $expectedResult = [
            'data' => [
                'primaryKeySimplePaginationQuery' => [
                    'current_page' => 1,
                    'data' => [],
                    'from' => null,
                    'has_more_pages' => false,
                    'per_page' => 1,
                    'to' => null,
                ],
            ],
        ];
        self::assertEquals($expectedResult, $result);
    }
}
