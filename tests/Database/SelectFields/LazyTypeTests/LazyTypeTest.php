<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\LazyTypeTests;

use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\Support\Models\User;
use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;
use Rebing\GraphQL\Tests\TestCaseDatabase;

/**
 * Test that SelectFields works when field types are defined as callable thunks.
 *
 * User and Post have a circular type reference:
 *   User -> posts -> Post -> user -> User
 *
 * webonyx/graphql-php supports `'type' => fn() => ...` on field definitions
 * to break such cycles (see graphql-php commit fdc5808, "Lazy loading of types").
 *
 * SelectFields accesses `$fieldObject->config['type']` directly instead of
 * `$fieldObject->getType()`, so it receives a Closure instead of a Type when
 * a lazy thunk is used, causing a crash.
 */
class LazyTypeTest extends TestCaseDatabase
{
    use SqlAssertionTrait;

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'query' => [
                UsersQuery::class,
            ],
        ]);

        $app['config']->set('graphql.types', [
            PostType::class,
            UserType::class,
        ]);
    }

    /**
     * Querying a relation field (User.posts) whose type is a lazy thunk crashes
     * because SelectFields reads config['type'] (a Closure) instead of getType().
     *
     * Hits SelectFields line 240:
     *   $newParentType = $parentType->getField($key)->config['type']
     */
    public function testSelectFieldsWithLazyTypeOnHasManyRelation(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'name' => 'User Name',
        ]);

        Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'Post Title',
        ]);

        $query = <<<'GRAQPHQL'
        {
          users {
            id
            posts {
              id
              title
            }
          }
        }
        GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($query);

        $this->assertSqlQueries(
            <<<'SQL'
        select "users"."id" from "users";
        select "posts"."id", "posts"."title", "posts"."user_id" from "posts" where "posts"."user_id" in (?) order by "posts"."id" asc;
        SQL,
        );

        $expectedResult = [
            'data' => [
                'users' => [
                    [
                        'id' => (string) $user->id,
                        'posts' => [
                            [
                                'id' => (string) $user->posts[0]->id,
                                'title' => 'Post Title',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        self::assertEquals($expectedResult, $result);
    }

    /**
     * Querying the full circular path (User -> posts -> Post -> user -> User)
     * exercises both lazy thunks and hits SelectFields config['type'] on both
     * the hasMany (User.posts) and belongsTo (Post.user) sides.
     */
    public function testSelectFieldsWithCircularLazyTypes(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'name' => 'User Name',
        ]);

        Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'Post Title',
        ]);

        $query = <<<'GRAQPHQL'
        {
          users {
            id
            posts {
              id
              title
              user {
                id
                name
              }
            }
          }
        }
        GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($query);

        $this->assertSqlQueries(
            <<<'SQL'
        select "users"."id" from "users";
        select "posts"."id", "posts"."title", "posts"."user_id" from "posts" where "posts"."user_id" in (?) order by "posts"."id" asc;
        select "users"."id", "users"."name" from "users" where "users"."id" in (?);
        SQL,
        );

        $expectedResult = [
            'data' => [
                'users' => [
                    [
                        'id' => (string) $user->id,
                        'posts' => [
                            [
                                'id' => (string) $user->posts[0]->id,
                                'title' => 'Post Title',
                                'user' => [
                                    'id' => (string) $user->id,
                                    'name' => 'User Name',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        self::assertEquals($expectedResult, $result);
    }
}
