<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\PrimaryKeyTests;

use Rebing\GraphQL\Tests\Support\Models\Comment;
use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;
use Rebing\GraphQL\Tests\TestCaseDatabase;

class CursorPaginationTest extends TestCaseDatabase
{
    use SqlAssertionTrait;

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'query' => [
                PrimaryKeyQuery::class,
                PrimaryKeyCursorPaginationQuery::class,
            ],
        ]);

        $app['config']->set('graphql.types', [
            CommentType::class,
            PostType::class,
        ]);
    }

    public function testCursorPagination(): void
    {
        /** @var Post $post */
        $post = factory(Post::class)->create([
            'title' => 'post 1',
        ]);
        factory(Comment::class)->create([
            'title' => 'post 1 comment 1',
            'post_id' => $post->id,
        ]);
        /** @var Post $post */
        $post = factory(Post::class)->create([
            'title' => 'post 2',
        ]);
        factory(Comment::class)->create([
            'title' => 'post 2 comment 1',
            'post_id' => $post->id,
        ]);

        $query = <<<'GRAQPHQL'
        {
          primaryKeyCursorPaginationQuery {
            data {
              title
              comments {
                title
              }
            }
            path
            per_page
            next_cursor
            next_page_url
            prev_cursor
            prev_page_url
          }
        }
        GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($query);

        $this->assertSqlQueries(
            <<<'SQL'
            select "posts"."title", "posts"."id" from "posts" order by "posts"."id" asc limit 2;
            select "comments"."title", "comments"."post_id", "comments"."id" from "comments" where "comments"."post_id" in (?, ?) order by "comments"."id" asc;
            SQL
        );

        $expectedResult = [
            'data' => [
                'primaryKeyCursorPaginationQuery' => [
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
                    'path' => 'http://localhost/graphql',
                    'per_page' => 1,
                    'next_cursor' => 'eyJwb3N0cy5pZCI6MSwiX3BvaW50c1RvTmV4dEl0ZW1zIjp0cnVlfQ',
                    'next_page_url' => 'http://localhost/graphql?cursor=eyJwb3N0cy5pZCI6MSwiX3BvaW50c1RvTmV4dEl0ZW1zIjp0cnVlfQ',
                    'prev_cursor' => null,
                    'prev_page_url' => null,
                ],
            ],
        ];
        self::assertEquals($expectedResult, $result);
    }

    public function testCursorPaginationHasNoMorePage(): void
    {
        /** @var Post $post */
        $post = factory(Post::class)->create([
            'title' => 'post 1',
        ]);
        factory(Comment::class)->create([
            'title' => 'post 1 comment 1',
            'post_id' => $post->id,
        ]);
        $query = <<<'GRAQPHQL'
        {
          primaryKeyCursorPaginationQuery {
            data {
              title
              comments {
                title
              }
            }
            path
            per_page
            next_cursor
            next_page_url
            prev_cursor
            prev_page_url
          }
        }
        GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($query);

        $this->assertSqlQueries(
            <<<'SQL'
            select "posts"."title", "posts"."id" from "posts" order by "posts"."id" asc limit 2;
            select "comments"."title", "comments"."post_id", "comments"."id" from "comments" where "comments"."post_id" in (?) order by "comments"."id" asc;
            SQL
        );

        $expectedResult = [
            'data' => [
                'primaryKeyCursorPaginationQuery' => [
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
                    'path' => 'http://localhost/graphql',
                    'per_page' => 1,
                    'next_cursor' => null,
                    'next_page_url' => null,
                    'prev_cursor' => null,
                    'prev_page_url' => null,
                ],
            ],
        ];
        self::assertEquals($expectedResult, $result);
    }

    public function testCursorPaginationReturnEmptyList(): void
    {
        $query = <<<'GRAQPHQL'
        {
          primaryKeyCursorPaginationQuery {
            data {
              title
              comments {
                title
              }
            }
            path
            per_page
            next_cursor
            next_page_url
            prev_cursor
            prev_page_url
          }
        }
        GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($query);

        $this->assertSqlQueries(
            <<<'SQL'
            select "posts"."title", "posts"."id" from "posts" order by "posts"."id" asc limit 2;
            SQL
        );

        $expectedResult = [
            'data' => [
                'primaryKeyCursorPaginationQuery' => [
                    'data' => [],
                    'path' => 'http://localhost/graphql',
                    'per_page' => 1,
                    'next_cursor' => null,
                    'next_page_url' => null,
                    'prev_cursor' => null,
                    'prev_page_url' => null,
                ],
            ],
        ];
        self::assertEquals($expectedResult, $result);
    }
}
