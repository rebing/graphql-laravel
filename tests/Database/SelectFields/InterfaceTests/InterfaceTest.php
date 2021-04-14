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

    public function testGeneratedSqlQuery(): void
    {
        factory(Post::class)->create([
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

        $result = $this->graphql($graphql);

        $this->assertSqlQueries(
            <<<'SQL'
select * from "posts";
SQL
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

    public function testGeneratedRelationSqlQuery(): void
    {
        /** @var Post $post */
        $post = factory(Post::class)
            ->create([
                'title' => 'Title of the post',
            ]);
        factory(Comment::class)
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

        $result = $this->graphql($graphql);

        $this->assertSqlQueries(
            <<<'SQL'
select * from "posts";
select "comments"."title", "comments"."post_id", "comments"."id" from "comments" where "comments"."post_id" in (?) and "id" >= ? order by "comments"."id" asc;
SQL
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

    public function testGeneratedInterfaceFieldSqlQuery(): void
    {
        /** @var Post $post */
        $post = factory(Post::class)
            ->create([
                'title' => 'Title of the post',
            ]);
        factory(Comment::class)
            ->create([
                'title' => 'Title of the comment',
                'post_id' => $post->id,
            ]);

        /** @var User $user */
        $user = factory(User::class)->create();
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

        $result = $this->graphql($graphql);

        $this->assertSqlQueries(
            <<<'SQL'
select "users"."id" from "users";
select "likes"."likable_id", "likes"."likable_type", "likes"."user_id", "likes"."id" from "likes" where "likes"."user_id" in (?);
select * from "posts" where "posts"."id" in (?);
SQL
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

    public function testGeneratedInterfaceFieldInlineFragmentsAndAlias(): void
    {
        /** @var Post $post */
        $post = factory(Post::class)
            ->create([
                'title' => 'Title of the post',
            ]);
        factory(Comment::class)
            ->create([
                'title' => 'Title of the comment',
                'post_id' => $post->id,
            ]);

        /** @var User $user */
        $user = factory(User::class)->create();
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

        $result = $this->graphql($graphql);

        $this->assertSqlQueries(
            <<<'SQL'
select "users"."id" from "users";
select "likes"."likable_id", "likes"."likable_type", "likes"."user_id", "likes"."id" from "likes" where "likes"."user_id" in (?);
select * from "posts" where "posts"."id" in (?);
SQL
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

    public function testGeneratedInterfaceFieldWithRelationSqlQuery(): void
    {
        /** @var Post $post */
        $post = factory(Post::class)
            ->create([
                'title' => 'Title of the post',
            ]);
        factory(Comment::class)
            ->create([
                'title' => 'Title of the comment',
                'post_id' => $post->id,
            ]);

        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var User $user2 */
        $user2 = factory(User::class)->create();
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

        $result = $this->graphql($graphql);

        $this->assertSqlQueries(
            <<<'SQL'
select "users"."id" from "users";
select "likes"."likable_id", "likes"."likable_type", "likes"."user_id", "likes"."id" from "likes" where "likes"."user_id" in (?, ?);
select * from "posts" where "posts"."id" in (?);
select "likes"."id", "likes"."likable_id", "likes"."likable_type" from "likes" where "likes"."likable_id" in (?) and "likes"."likable_type" = ? and 0=0;
SQL
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

    public function testGeneratedInterfaceFieldWithRelationAndCustomQueryOnInterfaceSqlQuery(): void
    {
        /** @var Post $post */
        $post = factory(Post::class)
            ->create([
                'title' => 'Title of the post',
            ]);
        /** @var Comment $comment */
        $comment = factory(Comment::class)
            ->create([
                'title' => 'Title of the comment',
                'post_id' => $post->id,
            ]);

        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var User $user2 */
        $user2 = factory(User::class)->create();
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

        $result = $this->graphql($graphql);

        $this->assertSqlQueries(
            <<<'SQL'
select "users"."id" from "users";
select "likes"."likable_id", "likes"."likable_type", "likes"."user_id", "likes"."id" from "likes" where "likes"."user_id" in (?, ?);
select * from "comments" where "comments"."id" in (?);
select "likes"."id", "likes"."likable_id", "likes"."likable_type" from "likes" where "likes"."likable_id" in (?) and "likes"."likable_type" = ? and 1=1;
SQL
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

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'query' => [
                ExampleInterfaceQuery::class,
                UserQuery::class,
            ],
        ]);

        $app['config']->set('graphql.schemas.custom', null);

        $app['config']->set('graphql.types', [
            ExampleInterfaceType::class,
            InterfaceImpl1Type::class,
            ExampleRelationType::class,
            LikableInterfaceType::class,
            PostType::class,
            CommentType::class,
            UserType::class,
            LikeType::class,
        ]);
    }
}
