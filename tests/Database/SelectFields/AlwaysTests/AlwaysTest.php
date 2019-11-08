<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\AlwaysTests;

use Rebing\GraphQL\Tests\Support\Models\Comment;
use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;
use Rebing\GraphQL\Tests\TestCaseDatabase;

class AlwaysTest extends TestCaseDatabase
{
    use SqlAssertionTrait;

    public function testAlwaysSingleField(): void
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
  alwaysQuery {
    body
    title
    comments_always_single_field {
      id
    }
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->graphql($query);

        $this->assertSqlQueries(<<<'SQL'
select "posts"."body", "posts"."title", "posts"."id" from "posts";
select "comments"."id", "comments"."post_id", "comments"."body" from "comments" where "comments"."post_id" in (?) order by "comments"."id" asc;
SQL
        );

        $expectedResult = [
            'data' => [
                'alwaysQuery' => [
                    [
                        'body' => 'post body',
                        'title' => 'post title',
                        'comments_always_single_field' => [
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

    public function testAlwaysSingleMultipleFieldInString(): void
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
  alwaysQuery {
    body
    title
    comments_always_multiple_fields_in_string {
      id
    }
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->graphql($query);

        $this->assertSqlQueries(<<<'SQL'
select "posts"."body", "posts"."title", "posts"."id" from "posts";
select "comments"."id", "comments"."post_id", "comments"."body", "comments"."title" from "comments" where "comments"."post_id" in (?) order by "comments"."id" asc;
SQL
        );

        $expectedResult = [
            'data' => [
                'alwaysQuery' => [
                    [
                        'body' => 'post body',
                        'title' => 'post title',
                        'comments_always_multiple_fields_in_string' => [
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

    public function testAlwaysSingleMultipleFieldInArray(): void
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
  alwaysQuery {
    body
    title
    comments_always_multiple_fields_in_array {
      id
    }
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->graphql($query);

        $this->assertSqlQueries(<<<'SQL'
select "posts"."body", "posts"."title", "posts"."id" from "posts";
select "comments"."id", "comments"."post_id", "comments"."body", "comments"."title" from "comments" where "comments"."post_id" in (?) order by "comments"."id" asc;
SQL
        );

        $expectedResult = [
            'data' => [
                'alwaysQuery' => [
                    [
                        'body' => 'post body',
                        'title' => 'post title',
                        'comments_always_multiple_fields_in_array' => [
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

    public function testAlwaysSameFieldTwice(): void
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
  alwaysQuery {
    body
    title
    comments_always_same_field_twice {
      id
    }
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->graphql($query);

        $this->assertSqlQueries(<<<'SQL'
select "posts"."body", "posts"."title", "posts"."id" from "posts";
select "comments"."id", "comments"."post_id", "comments"."body" from "comments" where "comments"."post_id" in (?) order by "comments"."id" asc;
SQL
        );

        $expectedResult = [
            'data' => [
                'alwaysQuery' => [
                    [
                        'body' => 'post body',
                        'title' => 'post title',
                        'comments_always_same_field_twice' => [
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
                AlwaysQuery::class,
            ],
        ]);

        $app['config']->set('graphql.schemas.custom', null);

        $app['config']->set('graphql.types', [
            CommentType::class,
            PostType::class,
        ]);
    }
}
