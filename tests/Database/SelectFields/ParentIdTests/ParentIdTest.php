<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\ParentIdTests;

use Rebing\GraphQL\Tests\TestCaseDatabase;
use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\Support\Models\Comment;
use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;

class ParentIdTest extends TestCaseDatabase
{
    use SqlAssertionTrait;

    public function testParentIdRetrievedWhenSelectingRelations()
    {
        /** @var Post $post */
        $post = factory(Post::class)->create();
        /** @var Comment $comment */
        $comment = factory(Comment::class)->create(['post_id' => $post->id,]);

        $query = <<<'GRAQPHQL'
{
  parentIdQuery {
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
                'parentIdQuery' => [
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

    public function testParentIdRetrievedWhenSelectingRelationsAndResultPaginated()
    {
        /** @var Post $post */
        $post = factory(Post::class)->create();
        /** @var Comment $comment */
        $comment = factory(Comment::class)->create(['post_id' => $post->id,]);

        $query = <<<'GRAQPHQL'
{
  parentIdPaginationQuery {
    data {
      comments {
        title
      }
    }
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->graphql($query);

        $this->assertSqlQueries(<<<'SQL'
select count(*) as aggregate from "posts";
select "posts"."id" from "posts" limit 10 offset 0;
select "comments"."title", "comments"."post_id", "comments"."id" from "comments" where "comments"."post_id" in (?) order by "comments"."id" asc;
SQL
        );

        $expectedResult = [
            'data' => [
                'parentIdPaginationQuery' => [
                    'data' => [
                        [
                            'comments' => [
                                [
                                    'title' => $comment->title,
                                ],
                            ],
                        ],
                    ]

                ],
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
                ParentIdPaginationQuery::class
            ],
        ]);

        $app['config']->set('graphql.types', [
            CommentType::class,
            PostType::class,
        ]);
    }
}
