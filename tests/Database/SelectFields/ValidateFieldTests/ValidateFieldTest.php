<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\ValidateFieldTests;

use Rebing\GraphQL\Tests\TestCaseDatabase;
use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\Support\Models\Comment;
use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;

class ValidateFieldTest extends TestCaseDatabase
{
    use SqlAssertionTrait;

    public function testSelectableFalse(): void
    {
        /** @var Post $post */
        $post = factory(Post::class)
            ->create([
                'body' => 'post body',
                'title' => 'post title',
            ]);
        $comment = factory(Comment::class)
            ->create([
                'body' => 'comment body',
                'post_id' => $post->id,
                'title' => 'comment title',
            ]);

        $query = <<<'GRAQPHQL'
{
  validateFields {
    body_selectable_false
    title
    comments {
      id
    }
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->graphql($query);

        $this->assertSqlQueries(<<<'SQL'
select "posts"."title", "posts"."id" from "posts";
select "comments"."id", "comments"."post_id" from "comments" where "comments"."post_id" in (?) order by "comments"."id" asc;
SQL
        );

        $expectedResult = [
            'data' => [
                'validateFields' => [
                    [
                        'body_selectable_false' => null,
                        'title' => 'post title',
                        'comments' => [
                            [
                                'id' => (string) $comment->id,
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->assertEquals($expectedResult, $result);
    }

    public function testSelectableNull(): void
    {
        /** @var Post $post */
        $post = factory(Post::class)
            ->create([
                'body' => 'post body',
                'title' => 'post title',
            ]);
        $comment = factory(Comment::class)
            ->create([
                'body' => 'comment body',
                'post_id' => $post->id,
                'title' => 'comment title',
            ]);

        $query = <<<'GRAQPHQL'
{
  validateFields {
    body_selectable_null
    title
    comments {
      id
    }
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->graphql($query);

        $this->assertSqlQueries(<<<'SQL'
select "posts"."body", "posts"."title", "posts"."id" from "posts";
select "comments"."id", "comments"."post_id" from "comments" where "comments"."post_id" in (?) order by "comments"."id" asc;
SQL
        );

        $expectedResult = [
            'data' => [
                'validateFields' => [
                    [
                        'body_selectable_null' => 'post body',
                        'title' => 'post title',
                        'comments' => [
                            [
                                'id' => (string) $comment->id,
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->assertEquals($expectedResult, $result);
    }

    public function testSelectableTrue(): void
    {
        /** @var Post $post */
        $post = factory(Post::class)
            ->create([
                'body' => 'post body',
                'title' => 'post title',
            ]);
        $comment = factory(Comment::class)
            ->create([
                'body' => 'comment body',
                'post_id' => $post->id,
                'title' => 'comment title',
            ]);

        $query = <<<'GRAQPHQL'
{
  validateFields {
    body_selectable_true
    title
    comments {
      id
    }
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->graphql($query);

        $this->assertSqlQueries(<<<'SQL'
select "posts"."body", "posts"."title", "posts"."id" from "posts";
select "comments"."id", "comments"."post_id" from "comments" where "comments"."post_id" in (?) order by "comments"."id" asc;
SQL
        );

        $expectedResult = [
            'data' => [
                'validateFields' => [
                    [
                        'body_selectable_true' => 'post body',
                        'title' => 'post title',
                        'comments' => [
                            [
                                'id' => (string) $comment->id,
                            ],
                        ],
                    ],
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
                ValidateFieldsQuery::class,
            ],
        ]);

        $app['config']->set('graphql.schemas.custom', null);

        $app['config']->set('graphql.types', [
            CommentType::class,
            PostType::class,
        ]);
    }
}
