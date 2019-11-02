<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\DepthTests;

use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\Support\Models\User;
use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;
use Rebing\GraphQL\Tests\TestCaseDatabase;

class DepthTest extends TestCaseDatabase
{
    use SqlAssertionTrait;

    public function testDefaultDepthExceeded(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        factory(Post::class)->create([
            'user_id' => $user->id,
        ]);

        $graphql = <<<'GRAQPHQL'
{
  users {
    id
    posts {
      id
      user {
        id
        posts {
          id
          user {
            id
            posts {
              id
              user {
                id
              }
            }
          }
        }
      }
    }
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->graphql($graphql, [
            'expectErrors' => true,
        ]);

        $this->assertCount(1, $result['errors']);
        $this->assertSame('Internal server error', $result['errors'][0]['message']);
        $this->assertSame('SQLSTATE[HY000]: General error: 1 no such column: posts.user (SQL: select "posts"."id", "posts"."user", "posts"."user_id" from "posts" where "posts"."user_id" in (1) order by "posts"."id" asc)', $result['errors'][0]['debugMessage']);
    }

    public function testDefaultDepthAdjusted(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $post = factory(Post::class)->create([
            'user_id' => $user->id,
        ]);

        $graphql = <<<'GRAQPHQL'
{
  users(depth: 6) {
    id
    posts {
      id
      user {
        id
        posts {
          id
          user {
            id
            posts {
              id
              user {
                id
              }
            }
          }
        }
      }
    }
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->graphql($graphql);

        $this->assertSqlQueries(<<<'SQL'
select "users"."id" from "users";
select "posts"."id", "posts"."user_id" from "posts" where "posts"."user_id" in (?) order by "posts"."id" asc;
select "users"."id" from "users" where "users"."id" in (?);
select "posts"."id", "posts"."user_id" from "posts" where "posts"."user_id" in (?) order by "posts"."id" asc;
select "users"."id" from "users" where "users"."id" in (?);
select "posts"."id", "posts"."user_id" from "posts" where "posts"."user_id" in (?) order by "posts"."id" asc;
select "users"."id" from "users" where "users"."id" in (?);
SQL
        );

        $expectedResult = [
            'data' => [
                'users' => [
                    [
                        'id' => (string) $user->id,
                        'posts' => [
                            [
                                'id' => (string) $post->id,
                                'user' => [
                                    'id' => (string) $user->id,
                                    'posts' => [
                                        [
                                            'id' => (string) $post->id,
                                            'user' => [
                                                'id' => (string) $user->id,
                                                'posts' => [
                                                    [
                                                        'id' => (string) $post->id,
                                                        'user' => [
                                                            'id' => (string) $user->id,
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->assertSame($expectedResult, $result);
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
            PostType::class,
            UserType::class,
        ]);
    }
}
