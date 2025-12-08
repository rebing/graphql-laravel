<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\ComputedPropertiesTests;

use Illuminate\Support\Carbon;
use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\Support\Models\User;
use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;
use Rebing\GraphQL\Tests\TestCaseDatabase;

class ComputedPropertiesTest extends TestCaseDatabase
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

    public function testComputedProperty(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
        ]);

        /** @var Post $post */
        $post = Post::factory()->create([
            'user_id' => $user->id,
        ]);

        $post->published_at = Carbon::now();
        $post->save();

        $query = <<<'GRAQPHQL'
{
  users {
    id
    name
    posts {
      id
      isPublished
    }
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($query);

        $this->assertSqlQueries(
            <<<'SQL'
select "users"."id", "users"."name" from "users";
select "posts"."id", "posts"."published_at", "posts"."user_id" from "posts" where "posts"."user_id" in (?) order by "posts"."id" asc;
SQL,
        );

        $expectedResult = [
            'data' => [
                'users' => [
                    [
                        'id' => (string) $user->id,
                        'name' => $user->name,
                        'posts' => [
                            [
                                'id' => (string) $post->id,
                                'isPublished' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];
        self::assertSame($expectedResult, $result);
    }
}
