<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\ComputedPropertiesTests;

use Rebing\GraphQL\Tests\TestCaseDatabase;
use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\Support\Models\User;
use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;

class ComputedPropertiesTest extends TestCaseDatabase
{
    use SqlAssertionTrait;

    /**
     * Once https://github.com/rebing/graphql-laravel/issues/377 is fixed,
     * the test needs to be changed, showing that the computed properties
     * evaluate correctly, or perhaps throw an error if not properly configured.
     */
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
select "posts"."id", "posts"."user_id" from "posts" where "posts"."user_id" in (?) order by "posts"."id" asc;
SQL
        );

        $expectedResult = [
            'data' => [
                'users' => [
                    [
                        'id' => $user->id,
                        'name' => $user->name,
                        'posts' => [
                            [
                                'id' => $post->id,
                                // "isPublished" => $post->is_published,
                                // So, is_published is not being selected and Post::getIsPublishedAttributes evaluates to false.
                                'isPublished' => false,
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
