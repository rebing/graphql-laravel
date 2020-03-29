<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\QueryArgsTest;

use Rebing\GraphQL\Tests\Support\Models\Comment;
use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\Support\Models\User;
use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;
use Rebing\GraphQL\Tests\TestCaseDatabase;
use Rebing\GraphQL\GraphQLController;

class QueryArgsTest extends TestCaseDatabase
{
    use SqlAssertionTrait;

    public function testAliasing(): void
    {
        /** @var User[] $johns */
        $johns = factory(User::class, 2)
            ->create([
                'name' => 'John'
            ]);
        $brian = factory(User::class)
            ->create([
                'name' => 'Brian'
            ]);

        $graphql = <<<'GRAQPHQL'
{
  users(select: true, with: true) {
    id
    name
  }
  brians: users(name: "Brian", select: true, with: true) {
    id
    name
  }
}
GRAQPHQL;
        $this->sqlCounterReset();

        $result = $this->graphql($graphql);

        $this->assertSqlQueries(
            <<<'SQL'
select "users"."id", "users"."name" from "users" order by "users"."id" asc;
select "users"."id", "users"."name" from "users" where "name" = ? order by "users"."id" asc;
SQL
        );

        $expectedResult = [
            'data' => [
                'users' => [
                    [
                        'id' => "{$johns[0]->id}",
                        'name' => $johns[0]->name,
                    ],
                    [
                        'id' => "{$johns[1]->id}",
                        'name' => $johns[1]->name,
                    ],
                    [
                        'id' => "$brian->id",
                        'name' => $brian->name,
                    ],
                ],
                'brians' => [
                    [
                        'id' => "$brian->id",
                        'name' => $brian->name,
                    ],
                ],
            ],
        ];
        $this->assertSame($expectedResult, $result);
    }

    public function testAliasingNested(): void
    {
        /** @var User[] $users */
        $users = factory(User::class, 1)
            ->create()
            ->each(function (User $user): void {
                factory(Post::class)
                    ->create([
                        'title' => 'First post',
                        'flag' => true,
                        'user_id' => $user->id,
                        'published_at' => "2010-02-15"
                    ]);

                factory(Post::class)
                    ->create([
                        'title' => 'Second post',
                        'flag' => true,
                        'user_id' => $user->id,
                        'published_at' => "2030-05-19"
                    ]);
                factory(Post::class)
                    ->create([
                        'title' => 'Third post',
                        'flag' => true,
                        'user_id' => $user->id,
                        'published_at' => "2000-05-19"
                    ]);

            });

        $graphql = <<<'GRAQPHQL'
{
  users(select: true, with: true) {
    id
    name
    posts(publishedAfter: "2009-03-31") {
      id
      body
      title
    }
    alias1: posts(flag: true, publishedAfter: "2030-03-31") {
      id
      body
      title
    }
    alias2: posts {
      id
      body
      title
    }
  }
}
GRAQPHQL;
        $this->sqlCounterReset();

        $result = $this->graphql($graphql);

        $this->assertSqlQueries(
            <<<'SQL'
select "users"."id", "users"."name" from "users" order by "users"."id" asc;
select "posts"."id", "posts"."body", "posts"."title", "posts"."user_id" from "posts" where "posts"."user_id" in (?) and "posts"."published_at" > ? order by "posts"."id" asc;
select "posts"."id", "posts"."body", "posts"."title", "posts"."user_id" from "posts" where "posts"."user_id" in (?) and "posts"."flag" = ? and "posts"."published_at" > ? order by "posts"."id" asc;
select "posts"."id", "posts"."body", "posts"."title", "posts"."user_id" from "posts" where "posts"."user_id" in (?) order by "posts"."id" asc;
SQL
        );

        $expectedResult = [
            'data' => [
                'users' => [
                    [
                        'id' => (string) $users[0]->id,
                        'name' => $users[0]->name,
                        'posts' => 
                            $users[0]->posts->only([1,2])->map(function($post) {
                                return [
                                    'id' => "$post->id",
                                    'body' => $post->body,
                                    'title' => $post->title,
                                ];
                            })->toArray(), 
                        'alias1' => $users[0]->posts->only([2])->map(function($post) {
                                return [
                                    'id' => "$post->id",
                                    'body' => $post->body,
                                    'title' => $post->title,
                                ];
                            })->toArray(),
                        'alias2' => $users[0]->posts->map(function($post) {
                                return [
                                    'id' => "$post->id",
                                    'body' => $post->body,
                                    'title' => $post->title,
                                ];
                            })->toArray()
                    ]
                ],
            ],
        ];
        $this->assertSame($expectedResult, $result);
    }

    public function testAliasingDeepNested(): void
    {
        /** @var User[] $users */
        $users = factory(User::class, 1)
            ->create()
            ->each(function (User $user): void {
                $post1 = factory(Post::class)
                    ->create([
                        'title' => 'First post',
                        'flag' => true,
                        'user_id' => $user->id,
                        'published_at' => "2010-02-15"
                    ]);
                factory(Comment::class)
                    ->create([
                        'flag' => true,
                        'post_id' => $post1->id,
                    ]);
                factory(Comment::class)
                    ->create([
                        'post_id' => $post1->id,
                    ]);

                $post2 = factory(Post::class)
                    ->create([
                        'title' => 'Second post',
                        'flag' => true,
                        'user_id' => $user->id,
                        'published_at' => "2030-05-19"
                    ]);
                factory(Comment::class)
                    ->create([
                        'flag' => true,
                        'post_id' => $post2->id,
                    ]);
                factory(Comment::class)
                    ->create([
                        'post_id' => $post2->id,
                    ]);
                $post3 = factory(Post::class)
                    ->create([
                        'title' => 'Third post',
                        'flag' => true,
                        'user_id' => $user->id,
                        'published_at' => "2000-05-19"
                    ]);

            });

        $graphql = <<<'GRAQPHQL'
{
  users(select: true, with: true) {
    id
    name
    posts(publishedAfter: "2009-03-31") {
      title
      cmts1: comments {
        title
      }
    }
    alias1: posts(flag: true, publishedAfter: "2030-03-31") {
      title
      cmts2: comments(flag: true) {
        title
      }
    }
    alias2: posts {
      title  
      comments(flag: true) {
        title
      }
    }
  }
}
GRAQPHQL;
        $this->sqlCounterReset();

        $result = $this->graphql($graphql);

        $this->assertSqlQueries(
            <<<'SQL'
select "users"."id", "users"."name" from "users" order by "users"."id" asc;
select "posts"."title", "posts"."user_id", "posts"."id" from "posts" where "posts"."user_id" in (?) and "posts"."published_at" > ? order by "posts"."id" asc;
select "comments"."title", "comments"."post_id", "comments"."id" from "comments" where "comments"."post_id" in (?, ?) order by "comments"."id" asc;
select "posts"."title", "posts"."user_id", "posts"."id" from "posts" where "posts"."user_id" in (?) and "posts"."flag" = ? and "posts"."published_at" > ? order by "posts"."id" asc;
select "comments"."title", "comments"."post_id", "comments"."id" from "comments" where "comments"."post_id" in (?) and "comments"."flag" = ? order by "comments"."id" asc;
select "posts"."title", "posts"."user_id", "posts"."id" from "posts" where "posts"."user_id" in (?) order by "posts"."id" asc;
select "comments"."title", "comments"."post_id", "comments"."id" from "comments" where "comments"."post_id" in (?, ?, ?) and "comments"."flag" = ? order by "comments"."id" asc;
SQL
        );

        $user = $users[0];
        $post1 = $user->posts[0];
        $post2 = $user->posts[1];
        $post3 = $user->posts[2];
        $expectedResult = [
            'data' => [
                'users' => [
                    [
                        'id' => (string) $users[0]->id,
                        'name' => $users[0]->name,
                        'posts' => [
                            [
                                    'title' => $post1->title,
                                    'cmts1' => [
                                        [
                                            'title' =>$post1->comments[0]->title
                                        ],
                                        [
                                            'title' => $post1->comments[1]->title
                                        ]
                                    ]
                            ],
                            [
                                    'title' => $post2->title,
                                    'cmts1' => [
                                        [
                                            'title' =>$post2->comments[0]->title
                                        ],
                                        [
                                            'title' => $post2->comments[1]->title
                                        ]
                                    ]
                            ],
                        ], 
                        'alias1' => [  
                            [
                                    'title' => $post2->title,
                                    'cmts2' => [
                                        [
                                            'title' => $post2->comments[0]->title
                                        ]
                                    ]
                            ],
                        ],
                        'alias2' => [
                            [
                                    'title' => $post1->title,
                                    'comments' => [
                                        [
                                            'title' =>$post1->comments[0]->title
                                        ]
                                    ]
                            ],
                            [
                                    'title' => $post2->title,
                                    'comments' => [
                                        [
                                            'title' =>$post2->comments[0]->title
                                        ]
                                    ]
                            ],
                            [
                                    'title' => $post3->title,
                                    'comments' => [
                                    ]
                            ],
                        ],
                    ]
                ],
            ],
        ];

        $this->assertSame($expectedResult, $result);
    }


    public function testAliasingWithSameNameAsModelMethod(): void
    {
/** @var User[] $users */
        $users = factory(User::class, 1)
            ->create()
            ->each(function (User $user): void {
                factory(Post::class)
                    ->create([
                        'title' => 'First post',
                        'flag' => true,
                        'user_id' => $user->id,
                        'published_at' => "2010-02-15"
                    ]);

                factory(Post::class)
                    ->create([
                        'title' => 'Second post',
                        'flag' => true,
                        'user_id' => $user->id,
                        'published_at' => "2030-05-19"
                    ]);
                factory(Post::class)
                    ->create([
                        'title' => 'Third post',
                        'flag' => true,
                        'user_id' => $user->id,
                        'published_at' => "2000-05-19"
                    ]);

            });

        $graphql = <<<'GRAQPHQL'
{
  users(select: true, with: true) {
    id
    name
    posts(publishedAfter: "2009-03-31") {
      id
      body
      title
    }
    find: posts(flag: true, publishedAfter: "2030-03-31") {
      id
      body
      title
    }

  }
}
GRAQPHQL;
        $this->sqlCounterReset();

        $result = $this->graphql($graphql);

        $this->assertSqlQueries(
            <<<'SQL'
select "users"."id", "users"."name" from "users" order by "users"."id" asc;
select "posts"."id", "posts"."body", "posts"."title", "posts"."user_id" from "posts" where "posts"."user_id" in (?) and "posts"."published_at" > ? order by "posts"."id" asc;
select "posts"."id", "posts"."body", "posts"."title", "posts"."user_id" from "posts" where "posts"."user_id" in (?) and "posts"."flag" = ? and "posts"."published_at" > ? order by "posts"."id" asc;
SQL
        );

        $expectedResult = [
            'data' => [
                'users' => [
                    [
                        'id' => (string) $users[0]->id,
                        'name' => $users[0]->name,
                        'posts' => 
                            $users[0]->posts->only([1,2])->map(function($post) {
                                return [
                                    'id' => "$post->id",
                                    'body' => $post->body,
                                    'title' => $post->title,
                                ];
                            })->toArray(), 
                        'find' => $users[0]->posts->only([2])->map(function($post) {
                                return [
                                    'id' => "$post->id",
                                    'body' => $post->body,
                                    'title' => $post->title,
                                ];
                            })->toArray()
                    ]
                ],
            ],
        ];
        $this->assertSame($expectedResult, $result);
    }

    public function testCamelCaseAlias(): void
    {
/** @var User[] $users */
        $users = factory(User::class, 1)
            ->create()
            ->each(function (User $user): void {
                factory(Post::class)
                    ->create([
                        'title' => 'First post',
                        'flag' => true,
                        'user_id' => $user->id,
                        'published_at' => "2010-02-15"
                    ]);

                factory(Post::class)
                    ->create([
                        'title' => 'Second post',
                        'flag' => true,
                        'user_id' => $user->id,
                        'published_at' => "2030-05-19"
                    ]);
                factory(Post::class)
                    ->create([
                        'title' => 'Third post',
                        'flag' => true,
                        'user_id' => $user->id,
                        'published_at' => "2000-05-19"
                    ]);

            });

        $graphql = <<<'GRAQPHQL'
{
  users(select: true, with: true) {
    id
    name
    posts(publishedAfter: "2009-03-31") {
      id
      body
      title
    }
    mightyAlias: posts(flag: true, publishedAfter: "2030-03-31") {
      id
      body
      title
    }

  }
}
GRAQPHQL;
        $this->sqlCounterReset();

        $result = $this->graphql($graphql);

        $this->assertSqlQueries(
            <<<'SQL'
select "users"."id", "users"."name" from "users" order by "users"."id" asc;
select "posts"."id", "posts"."body", "posts"."title", "posts"."user_id" from "posts" where "posts"."user_id" in (?) and "posts"."published_at" > ? order by "posts"."id" asc;
select "posts"."id", "posts"."body", "posts"."title", "posts"."user_id" from "posts" where "posts"."user_id" in (?) and "posts"."flag" = ? and "posts"."published_at" > ? order by "posts"."id" asc;
SQL
        );

        $expectedResult = [
            'data' => [
                'users' => [
                    [
                        'id' => (string) $users[0]->id,
                        'name' => $users[0]->name,
                        'posts' => 
                            $users[0]->posts->only([1,2])->map(function($post) {
                                return [
                                    'id' => "$post->id",
                                    'body' => $post->body,
                                    'title' => $post->title,
                                ];
                            })->toArray(), 
                        'mightyAlias' => $users[0]->posts->only([2])->map(function($post) {
                                return [
                                    'id' => "$post->id",
                                    'body' => $post->body,
                                    'title' => $post->title,
                                ];
                            })->toArray()
                    ]
                ],
            ],
        ];
        $this->assertSame($expectedResult, $result);
    }


    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.controllers', GraphQLController::class.'@query');

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
