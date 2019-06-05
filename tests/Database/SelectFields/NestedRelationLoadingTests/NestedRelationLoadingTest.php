<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\NestedRelationLoadingTests;

use Rebing\GraphQL\Tests\TestCaseDatabase;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\Support\Models\User;
use Rebing\GraphQL\Tests\Support\Models\Comment;
use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;

class NestedRelationLoadingTest extends TestCaseDatabase
{
    use SqlAssertionTrait;

    public function testQueryNoSelectFields(): void
    {
        /** @var User[] $users */
        $users = factory(User::class, 2)
            ->create()
            ->each(function (User $user): void {
                factory(Post::class, 2)
                    ->create([
                        'user_id' => $user->id,
                    ])
                    ->each(function (Post $post): void {
                        factory(Comment::class, 2)
                            ->create([
                                'post_id' => $post->id,
                            ]);
                    });
            });

        $graphql = <<<'GRAQPHQL'
{
  users {
    id
    name
    posts {
      body
      id
      title
      comments {
        body
        id
        title
      }
    }
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = GraphQL::query($graphql);

        $this->assertSqlQueries(<<<'SQL'
select * from "users" order by "users"."id" asc;
select * from "posts" where "posts"."user_id" = ? and "posts"."user_id" is not null order by "posts"."id" asc;
select * from "comments" where "comments"."post_id" = ? and "comments"."post_id" is not null order by "comments"."id" asc;
select * from "comments" where "comments"."post_id" = ? and "comments"."post_id" is not null order by "comments"."id" asc;
select * from "posts" where "posts"."user_id" = ? and "posts"."user_id" is not null order by "posts"."id" asc;
select * from "comments" where "comments"."post_id" = ? and "comments"."post_id" is not null order by "comments"."id" asc;
select * from "comments" where "comments"."post_id" = ? and "comments"."post_id" is not null order by "comments"."id" asc;
SQL
        );

        $expectedResult = [
            'data' => [
                'users' => [
                    [
                        'id' => (string) $users[0]->id,
                        'name' => $users[0]->name,
                        'posts' => [
                            [
                                'body' => $users[0]->posts[0]->body,
                                'id' => (string) $users[0]->posts[0]->id,
                                'title' => $users[0]->posts[0]->title,
                                'comments' => [
                                    [
                                        'body' => $users[0]->posts[0]->comments[0]->body,
                                        'id' => (string) $users[0]->posts[0]->comments[0]->id,
                                        'title' => $users[0]->posts[0]->comments[0]->title,
                                    ],
                                    [
                                        'body' => $users[0]->posts[0]->comments[1]->body,
                                        'id' => (string) $users[0]->posts[0]->comments[1]->id,
                                        'title' => $users[0]->posts[0]->comments[1]->title,
                                    ],
                                ],
                            ],
                            [
                                'body' => $users[0]->posts[1]->body,
                                'id' => (string) $users[0]->posts[1]->id,
                                'title' => $users[0]->posts[1]->title,
                                'comments' => [
                                    [
                                        'body' => $users[0]->posts[1]->comments[0]->body,
                                        'id' => (string) $users[0]->posts[1]->comments[0]->id,
                                        'title' => $users[0]->posts[1]->comments[0]->title,
                                    ],
                                    [
                                        'body' => $users[0]->posts[1]->comments[1]->body,
                                        'id' => (string) $users[0]->posts[1]->comments[1]->id,
                                        'title' => $users[0]->posts[1]->comments[1]->title,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'id' => (string) $users[1]->id,
                        'name' => $users[1]->name,
                        'posts' => [
                            [
                                'body' => $users[1]->posts[0]->body,
                                'id' => (string) $users[1]->posts[0]->id,
                                'title' => $users[1]->posts[0]->title,
                                'comments' => [
                                    [
                                        'body' => $users[1]->posts[0]->comments[0]->body,
                                        'id' => (string) $users[1]->posts[0]->comments[0]->id,
                                        'title' => $users[1]->posts[0]->comments[0]->title,
                                    ],
                                    [
                                        'body' => $users[1]->posts[0]->comments[1]->body,
                                        'id' => (string) $users[1]->posts[0]->comments[1]->id,
                                        'title' => $users[1]->posts[0]->comments[1]->title,
                                    ],
                                ],
                            ],
                            [
                                'body' => $users[1]->posts[1]->body,
                                'id' => (string) $users[1]->posts[1]->id,
                                'title' => $users[1]->posts[1]->title,
                                'comments' => [
                                    [
                                        'body' => $users[1]->posts[1]->comments[0]->body,
                                        'id' => (string) $users[1]->posts[1]->comments[0]->id,
                                        'title' => $users[1]->posts[1]->comments[0]->title,
                                    ],
                                    [
                                        'body' => $users[1]->posts[1]->comments[1]->body,
                                        'id' => (string) $users[1]->posts[1]->comments[1]->id,
                                        'title' => $users[1]->posts[1]->comments[1]->title,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->assertEquals($expectedResult, $result);
    }

    public function testQuerySelect(): void
    {
        /** @var User[] $users */
        $users = factory(User::class, 2)
            ->create()
            ->each(function (User $user): void {
                factory(Post::class, 2)
                    ->create([
                        'user_id' => $user->id,
                    ])
                    ->each(function (Post $post): void {
                        factory(Comment::class, 2)
                            ->create([
                                'post_id' => $post->id,
                            ]);
                    });
            });

        $graphql = <<<'GRAQPHQL'
{
  users(select: true) {
    id
    name
    posts {
      body
      id
      title
      comments {
        body
        id
        title
      }
    }
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = GraphQL::query($graphql);

        $this->assertSame('SQLSTATE[HY000]: General error: 1 near "from": syntax error (SQL: select  from "users" order by "users"."id" asc)', $result['errors'][0]['debugMessage']);
    }

    public function testQueryWith(): void
    {
        /** @var User[] $users */
        $users = factory(User::class, 2)
            ->create()
            ->each(function (User $user): void {
                factory(Post::class, 2)
                    ->create([
                        'user_id' => $user->id,
                    ])
                    ->each(function (Post $post): void {
                        factory(Comment::class, 2)
                            ->create([
                                'post_id' => $post->id,
                            ]);
                    });
            });

        $graphql = <<<'GRAQPHQL'
{
  users(with: true) {
    id
    name
    posts {
      body
      id
      title
      comments {
        body
        id
        title
      }
    }
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = GraphQL::query($graphql);

        $this->assertSqlQueries(<<<'SQL'
select * from "users" order by "users"."id" asc;
select * from "posts" where "posts"."user_id" = ? and "posts"."user_id" is not null order by "posts"."id" asc;
select * from "comments" where "comments"."post_id" = ? and "comments"."post_id" is not null order by "comments"."id" asc;
select * from "comments" where "comments"."post_id" = ? and "comments"."post_id" is not null order by "comments"."id" asc;
select * from "posts" where "posts"."user_id" = ? and "posts"."user_id" is not null order by "posts"."id" asc;
select * from "comments" where "comments"."post_id" = ? and "comments"."post_id" is not null order by "comments"."id" asc;
select * from "comments" where "comments"."post_id" = ? and "comments"."post_id" is not null order by "comments"."id" asc;
SQL
        );

        $expectedResult = [
            'data' => [
                'users' => [
                    [
                        'id' => (string) $users[0]->id,
                        'name' => $users[0]->name,
                        'posts' => [
                            [
                                'body' => $users[0]->posts[0]->body,
                                'id' => (string) $users[0]->posts[0]->id,
                                'title' => $users[0]->posts[0]->title,
                                'comments' => [
                                    [
                                        'body' => $users[0]->posts[0]->comments[0]->body,
                                        'id' => (string) $users[0]->posts[0]->comments[0]->id,
                                        'title' => $users[0]->posts[0]->comments[0]->title,
                                    ],
                                    [
                                        'body' => $users[0]->posts[0]->comments[1]->body,
                                        'id' => (string) $users[0]->posts[0]->comments[1]->id,
                                        'title' => $users[0]->posts[0]->comments[1]->title,
                                    ],
                                ],
                            ],
                            [
                                'body' => $users[0]->posts[1]->body,
                                'id' => (string) $users[0]->posts[1]->id,
                                'title' => $users[0]->posts[1]->title,
                                'comments' => [
                                    [
                                        'body' => $users[0]->posts[1]->comments[0]->body,
                                        'id' => (string) $users[0]->posts[1]->comments[0]->id,
                                        'title' => $users[0]->posts[1]->comments[0]->title,
                                    ],
                                    [
                                        'body' => $users[0]->posts[1]->comments[1]->body,
                                        'id' => (string) $users[0]->posts[1]->comments[1]->id,
                                        'title' => $users[0]->posts[1]->comments[1]->title,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'id' => (string) $users[1]->id,
                        'name' => $users[1]->name,
                        'posts' => [
                            [
                                'body' => $users[1]->posts[0]->body,
                                'id' => (string) $users[1]->posts[0]->id,
                                'title' => $users[1]->posts[0]->title,
                                'comments' => [
                                    [
                                        'body' => $users[1]->posts[0]->comments[0]->body,
                                        'id' => (string) $users[1]->posts[0]->comments[0]->id,
                                        'title' => $users[1]->posts[0]->comments[0]->title,
                                    ],
                                    [
                                        'body' => $users[1]->posts[0]->comments[1]->body,
                                        'id' => (string) $users[1]->posts[0]->comments[1]->id,
                                        'title' => $users[1]->posts[0]->comments[1]->title,
                                    ],
                                ],
                            ],
                            [
                                'body' => $users[1]->posts[1]->body,
                                'id' => (string) $users[1]->posts[1]->id,
                                'title' => $users[1]->posts[1]->title,
                                'comments' => [
                                    [
                                        'body' => $users[1]->posts[1]->comments[0]->body,
                                        'id' => (string) $users[1]->posts[1]->comments[0]->id,
                                        'title' => $users[1]->posts[1]->comments[0]->title,
                                    ],
                                    [
                                        'body' => $users[1]->posts[1]->comments[1]->body,
                                        'id' => (string) $users[1]->posts[1]->comments[1]->id,
                                        'title' => $users[1]->posts[1]->comments[1]->title,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->assertEquals($expectedResult, $result);
    }

    public function testQuerySelectAndWith(): void
    {
        /** @var User[] $users */
        $users = factory(User::class, 2)
            ->create()
            ->each(function (User $user): void {
                factory(Post::class, 2)
                    ->create([
                        'user_id' => $user->id,
                    ])
                    ->each(function (Post $post): void {
                        factory(Comment::class, 2)
                            ->create([
                                'post_id' => $post->id,
                            ]);
                    });
            });

        $graphql = <<<'GRAQPHQL'
{
  users(select: true, with: true) {
    id
    name
    posts {
      body
      id
      title
      comments {
        body
        id
        title
      }
    }
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = GraphQL::query($graphql);

        $this->assertSame('SQLSTATE[HY000]: General error: 1 near "from": syntax error (SQL: select  from "users" order by "users"."id" asc)', $result['errors'][0]['debugMessage']);
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'query' => [
                UsersQuery::class,
            ],
        ]);

        $app['config']->set('graphql.schemas.custom', null);

        $app['config']->set('graphql.types', [
            CommentType::class,
            PostType::class,
            UserType::class,
        ]);
    }
}
