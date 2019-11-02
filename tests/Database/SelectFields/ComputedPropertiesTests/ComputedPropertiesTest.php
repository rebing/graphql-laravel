<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\ComputedPropertiesTests;

use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\Support\Models\User;
use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;
use Rebing\GraphQL\Tests\TestCaseDatabase;

class ComputedPropertiesTest extends TestCaseDatabase
{
    use SqlAssertionTrait;

    public function testComputedProperty(): void
    {
        $user = factory(User::class)->create([
        ]);

        $post = factory(Post::class)->create([
            'user_id' => $user->id,
        ]);

        $post->published_at = now();
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

        $result = $this->graphql($query);

        $this->assertSqlQueries(<<<'SQL'
select "users"."id", "users"."name" from "users";
select "posts"."id", "posts"."published_at", "posts"."user_id" from "posts" where "posts"."user_id" in (?) order by "posts"."id" asc;
SQL
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
