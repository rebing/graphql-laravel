<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\PrimaryKeyTests;

use Rebing\GraphQL\Tests\Support\Models\Comment;
use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;
use Rebing\GraphQL\Tests\TestCaseDatabase;

class PrimaryKeyTest extends TestCaseDatabase
{
    use SqlAssertionTrait;

    public function testPrimaryKeyRetrievedWhenSelectingRelations(): void
    {
        /** @var Post $post */
        $post = factory(Post::class)->create();
        /** @var Comment $comment */
        $comment = factory(Comment::class)->create(['post_id' => $post->id]);

        $query = <<<'GRAQPHQL'
{
  primaryKeyQuery {
    comments {
      title
    }
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->graphql($query);

        $this->assertSqlQueries(<<<'SQL'
select "posts"."id" from "posts";
select "comments"."title", "comments"."post_id", "comments"."id" from "comments" where "comments"."post_id" in (?) order by "comments"."id" asc;
SQL
        );

        $expectedResult = [
            'data' => [
                'primaryKeyQuery' => [
                    [
                        'comments' => [
                            [
                                'title' => $comment->title,
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->assertEquals($expectedResult, $result);
    }

    public function testPrimaryKeyRetrievedWhenSelectingRelationsAndResultPaginated(): void
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
  primaryKeyPaginationQuery {
    current_page
    data {
      title
      comments {
        title
      }
    }
    from
    has_more_pages
    last_page
    per_page
    to
    total
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->graphql($query);

        $this->assertSqlQueries(<<<'SQL'
select count(*) as aggregate from "posts";
select "posts"."title", "posts"."id" from "posts" limit 1 offset 0;
select "comments"."title", "comments"."post_id", "comments"."id" from "comments" where "comments"."post_id" in (?) order by "comments"."id" asc;
SQL
        );

        $expectedResult = [
            'data' => [
                'primaryKeyPaginationQuery' => [
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
                    'last_page' => 2,
                    'per_page' => 1,
                    'to' => 1,
                    'total' => 2,
                ],
            ],
        ];
        $this->assertEquals($expectedResult, $result);
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.lazyload_types', false);

        $app['config']->set('graphql.schemas.default', [
            'query' => [
                PrimaryKeyQuery::class,
                PrimaryKeyPaginationQuery::class,
            ],
        ]);

        $app['config']->set('graphql.types', [
            CommentType::class,
            PostType::class,
        ]);
    }
}
