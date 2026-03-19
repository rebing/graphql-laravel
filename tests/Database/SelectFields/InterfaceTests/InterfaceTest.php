<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\InterfaceTests;

use Rebing\GraphQL\Tests\Support\Models\Comment;
use Rebing\GraphQL\Tests\Support\Models\Like;
use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\Support\Models\User;
use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;
use Rebing\GraphQL\Tests\TestCaseDatabase;

class InterfaceTest extends TestCaseDatabase
{
    use SqlAssertionTrait;

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'query' => [
                ExampleInterfaceQuery::class,
                ExampleInterfaceGroupByQuery::class,
                UserQuery::class,
            ],
        ]);

        $app['config']->set('graphql.types', [
            CommentType::class,
            ExampleInterfaceType::class,
            ExampleRelationType::class,
            InterfaceImpl1Type::class,
            LikeType::class,
            PostType::class,
            UserType::class,
            // Interface deliberately last to guaranteed lazy loading them
            // works, otherwise tests without the fix in 9644b66faa8d37e7a1c36969470504614ab1c766
            // won't work!
            LikableInterfaceType::class,
        ]);
    }

    /**
     * Bug: the query only requests `title`, yet the generated SQL is
     * `select *` instead of selecting only the requested columns.
     *
     * @see https://github.com/rebing/graphql-laravel/issues/683
     */
    public function testGeneratedSqlQuery(): void
    {
        Post::factory()->create([
            'title' => 'Title of the post',
        ]);

        $graphql = <<<'GRAQPHQL'
{
  exampleInterfaceQuery {
    title
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($graphql);

        $this->assertSqlQueries(
            <<<'SQL'
select * from "posts";
SQL,
        );

        $expectedResult = [
            'data' => [
                'exampleInterfaceQuery' => [
                    [
                        'title' => 'Title of the post',
                    ],
                ],
            ],
        ];
        self::assertSame($expectedResult, $result);
    }

    /**
     * Bug: the posts query generates `select *` instead of selecting only
     * the requested columns, while the comments relation correctly selects
     * specific columns.
     *
     * @see https://github.com/rebing/graphql-laravel/issues/683
     */
    public function testGeneratedRelationSqlQuery(): void
    {
        /** @var Post $post */
        $post = Post::factory()
            ->create([
                'title' => 'Title of the post',
            ]);
        Comment::factory()
            ->create([
                'title' => 'Title of the comment',
                'post_id' => $post->id,
            ]);

        $graphql = <<<'GRAPHQL'
{
  exampleInterfaceQuery {
    id
    title
    exampleRelation {
      title
    }
  }
}
GRAPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($graphql);

        $this->assertSqlQueries(
            <<<'SQL'
select * from "posts";
select "comments"."title", "comments"."post_id", "comments"."id" from "comments" where "comments"."post_id" in (?) and "id" >= ? order by "comments"."id" asc;
SQL,
        );

        $expectedResult = [
            'data' => [
                'exampleInterfaceQuery' => [
                    [
                        'id' => (string) $post->id,
                        'title' => 'Title of the post',
                        'exampleRelation' => [
                            [
                                'title' => 'Title of the comment',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        self::assertSame($expectedResult, $result);
    }

    /**
     * Bug: the morph target (posts) generates `select *` instead of
     * selecting only the requested columns (`id`, `title`).
     *
     * @see https://github.com/rebing/graphql-laravel/issues/683
     */
    public function testGeneratedInterfaceFieldSqlQuery(): void
    {
        /** @var Post $post */
        $post = Post::factory()
            ->create([
                'title' => 'Title of the post',
            ]);
        Comment::factory()
            ->create([
                'title' => 'Title of the comment',
                'post_id' => $post->id,
            ]);

        /** @var User $user */
        $user = User::factory()->create();
        Like::create([
            'likable_id' => $post->id,
            'likable_type' => Post::class,
            'user_id' => $user->id,
        ]);

        $graphql = <<<'GRAPHQL'
{
  userQuery {
    id
    likes{
      likable{
        id
        title
      }
    }
  }
}
GRAPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($graphql);

        $this->assertSqlQueries(
            <<<'SQL'
select "users"."id" from "users";
select "likes"."likable_id", "likes"."likable_type", "likes"."user_id", "likes"."id" from "likes" where "likes"."user_id" in (?);
select * from "posts" where "posts"."id" in (?);
SQL,
        );

        $expectedResult = [
            'data' => [
                'userQuery' => [
                    [
                        'id' => (string) $user->id,
                        'likes' => [
                            [
                                'likable' => [
                                    'id' => (string) $post->id,
                                    'title' => $post->title,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        self::assertSame($expectedResult, $result);
    }

    /**
     * Bug: the morph target (posts) generates `select *` instead of
     * selecting only the requested columns (`id`, `title`, `created_at`,
     * `updated_at` via alias).
     *
     * @see https://github.com/rebing/graphql-laravel/issues/683
     */
    public function testGeneratedInterfaceFieldInlineFragmentsAndAlias(): void
    {
        /** @var Post $post */
        $post = Post::factory()
            ->create([
                'title' => 'Title of the post',
            ]);
        Comment::factory()
            ->create([
                'title' => 'Title of the comment',
                'post_id' => $post->id,
            ]);

        /** @var User $user */
        $user = User::factory()->create();
        Like::create([
            'likable_id' => $post->id,
            'likable_type' => Post::class,
            'user_id' => $user->id,
        ]);

        $graphql = <<<'GRAPHQL'
{
  userQuery {
    id
    likes{
      likable{
        id
        title
        ...on Post {
          created_at
          alias_updated_at
        }
      }
    }
  }
}
GRAPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($graphql);

        $this->assertSqlQueries(
            <<<'SQL'
select "users"."id" from "users";
select "likes"."likable_id", "likes"."likable_type", "likes"."user_id", "likes"."id" from "likes" where "likes"."user_id" in (?);
select * from "posts" where "posts"."id" in (?);
SQL,
        );

        $expectedResult = [
            'data' => [
                'userQuery' => [
                    [
                        'id' => (string) $user->id,
                        'likes' => [
                            [
                                'likable' => [
                                    'id' => (string) $post->id,
                                    'title' => $post->title,
                                    'created_at' => $post->created_at->toDateTimeString(),
                                    'alias_updated_at' => $post->updated_at->toDateTimeString(),
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        self::assertSame($expectedResult, $result);
    }

    /**
     * Bug: the morph target (posts) generates `select *` instead of
     * selecting only the requested columns.
     *
     * @see https://github.com/rebing/graphql-laravel/issues/683
     */
    public function testGeneratedInterfaceFieldWithRelationSqlQuery(): void
    {
        /** @var Post $post */
        $post = Post::factory()
            ->create([
                'title' => 'Title of the post',
            ]);
        Comment::factory()
            ->create([
                'title' => 'Title of the comment',
                'post_id' => $post->id,
            ]);

        /** @var User $user */
        $user = User::factory()->create();
        /** @var User $user2 */
        $user2 = User::factory()->create();
        $like1 = Like::create([
            'likable_id' => $post->id,
            'likable_type' => Post::class,
            'user_id' => $user->id,
        ]);
        $like2 = Like::create([
            'likable_id' => $post->id,
            'likable_type' => Post::class,
            'user_id' => $user2->id,
        ]);

        $graphql = <<<'GRAPHQL'
{
  userQuery {
    id
    likes{
      likable{
        id
        title
        likes{
          id
        }
      }
    }
  }
}
GRAPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($graphql);

        $this->assertSqlQueries(
            <<<'SQL'
select "users"."id" from "users";
select "likes"."likable_id", "likes"."likable_type", "likes"."user_id", "likes"."id" from "likes" where "likes"."user_id" in (?, ?);
select * from "posts" where "posts"."id" in (?);
select "likes"."id", "likes"."likable_id", "likes"."likable_type" from "likes" where "likes"."likable_id" in (?) and "likes"."likable_type" = ? and 0=0;
SQL,
        );

        $expectedResult = [
            'data' => [
                'userQuery' => [
                    [
                        'id' => (string) $user->id,
                        'likes' => [
                            [
                                'likable' => [
                                    'id' => (string) $post->id,
                                    'title' => $post->title,
                                    'likes' => [
                                        [
                                            'id' => (string) $like1->id,
                                        ],
                                        [
                                            'id' => (string) $like2->id,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'id' => (string) $user2->id,
                        'likes' => [
                            [
                                'likable' => [
                                    'id' => (string) $post->id,
                                    'title' => $post->title,
                                    'likes' => [
                                        [
                                            'id' => (string) $like1->id,
                                        ],
                                        [
                                            'id' => (string) $like2->id,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        self::assertSame($expectedResult, $result);
    }

    /**
     * Bug: the morph target (comments) generates `select *` instead of
     * selecting only the requested columns.
     *
     * @see https://github.com/rebing/graphql-laravel/issues/683
     */
    public function testGeneratedInterfaceFieldWithRelationAndCustomQueryOnInterfaceSqlQuery(): void
    {
        /** @var Post $post */
        $post = Post::factory()
            ->create([
                'title' => 'Title of the post',
            ]);
        /** @var Comment $comment */
        $comment = Comment::factory()
            ->create([
                'title' => 'Title of the comment',
                'post_id' => $post->id,
            ]);

        /** @var User $user */
        $user = User::factory()->create();
        /** @var User $user2 */
        $user2 = User::factory()->create();
        $like1 = Like::create([
            'likable_id' => $comment->id,
            'likable_type' => Comment::class,
            'user_id' => $user->id,
        ]);
        $like2 = Like::create([
            'likable_id' => $comment->id,
            'likable_type' => Comment::class,
            'user_id' => $user2->id,
        ]);

        $graphql = <<<'GRAPHQL'
{
  userQuery {
    id
    likes{
      likable{
        id
        title
        likes{
          id
        }
      }
    }
  }
}
GRAPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($graphql);

        $this->assertSqlQueries(
            <<<'SQL'
select "users"."id" from "users";
select "likes"."likable_id", "likes"."likable_type", "likes"."user_id", "likes"."id" from "likes" where "likes"."user_id" in (?, ?);
select * from "comments" where "comments"."id" in (?);
select "likes"."id", "likes"."likable_id", "likes"."likable_type" from "likes" where "likes"."likable_id" in (?) and "likes"."likable_type" = ? and 1=1;
SQL,
        );

        $expectedResult = [
            'data' => [
                'userQuery' => [
                    [
                        'id' => (string) $user->id,
                        'likes' => [
                            [
                                'likable' => [
                                    'id' => (string) $comment->id,
                                    'title' => $comment->title,
                                    'likes' => [
                                        [
                                            'id' => (string) $like1->id,
                                        ],
                                        [
                                            'id' => (string) $like2->id,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'id' => (string) $user2->id,
                        'likes' => [
                            [
                                'likable' => [
                                    'id' => (string) $comment->id,
                                    'title' => $comment->title,
                                    'likes' => [
                                        [
                                            'id' => (string) $like1->id,
                                        ],
                                        [
                                            'id' => (string) $like2->id,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        self::assertSame($expectedResult, $result);
    }

    /**
     * @see https://github.com/rebing/graphql-laravel/issues/683
     *
     * Bug: SelectFields forces `select *` for interface return types
     * (see SelectFields::handleFields, end of method) instead of selecting
     * only the requested fields. This makes GROUP BY fail on MySQL with
     * ONLY_FULL_GROUP_BY because non-aggregated columns not in the GROUP BY
     * clause are included via `*`.
     *
     * SQLite is lenient about GROUP BY, so this test passes, but the
     * generated SQL is wrong. On MySQL it would produce:
     *   "Syntax error or access violation: 1055 Expression #1 of SELECT list
     *    is not in GROUP BY clause and contains nonaggregated column..."
     *
     * Current (wrong):  select * from "posts" group by "title"
     * Expected (fixed): select "title" from "posts" group by "title"
     */
    public function testGeneratedSqlQueryWithGroupBy(): void
    {
        Post::factory()->create([
            'title' => 'Title of the post',
        ]);
        Post::factory()->create([
            'title' => 'Title of the post',
        ]);

        $graphql = <<<'GRAQPHQL'
{
  exampleInterfaceGroupByQuery {
    title
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($graphql);

        // Bug https://github.com/rebing/graphql-laravel/issues/683
        // Should be: select "title" from "posts" group by "title"
        $this->assertSqlQueries(
            <<<'SQL'
select * from "posts" group by "title";
SQL,
        );

        $expectedResult = [
            'data' => [
                'exampleInterfaceGroupByQuery' => [
                    [
                        'title' => 'Title of the post',
                    ],
                ],
            ],
        ];
        self::assertSame($expectedResult, $result);
    }
}
