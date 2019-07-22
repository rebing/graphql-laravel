<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\ParentIdTests;

use Rebing\GraphQL\Tests\TestCaseDatabase;
use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\Support\Models\Comment;
use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;

class PaginationWithLazyloadTest extends TestCaseDatabase
{
    use SqlAssertionTrait;

    public function testPaginationWhenLazyloadIsEnabled(): void
    {
        if (false === app('config')->get('graphql.lazyload_types')) {
            $this->markTestSkipped('Skipping test when lazyload_types=false');
        }

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
  parentIdPaginationQuery {
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

        $result = $this->graphql($query, [
            'expectErrors' => true,
        ]);

        $this->assertSqlQueries(<<<'SQL'
select count(*) as aggregate from "posts";
select "posts"."title" from "posts" limit 1 offset 0;
select "comments"."title", "comments"."post_id", "comments"."id" from "comments" where "comments"."post_id" in (?) order by "comments"."id" asc;
SQL
        );

        $expectedResult = [
            'errors' => [
                [
                    'debugMessage' => 'Type PostPagination not found.
Check that the config array key for the type matches the name attribute in the type\'s class.
It is required when \'lazyload_types\' is enabled',
                    'message' => 'Internal server error',
                    'extensions' => [
                        'category' => 'internal',
                    ],
                    'locations' => [
                        [
                            'line' => 2,
                            'column' => 3,
                        ],
                    ],
                    'path' => [
                        'parentIdPaginationQuery',
                    ],
                ],
            ],
            'data' => [
                'parentIdPaginationQuery' => null,
            ],
        ];
        $this->assertEquals($expectedResult, $result);
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'query' => [
                ParentIdQuery::class,
                ParentIdPaginationQuery::class,
            ],
        ]);

        $app['config']->set('graphql.types', [
            CommentType::class,
            PostType::class,
        ]);
    }
}
