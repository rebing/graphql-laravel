<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\AlwaysRelationTests;

use Rebing\GraphQL\Tests\TestCaseDatabase;
use Rebing\GraphQL\Tests\Support\Models\Like;
use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\Support\Models\User;
use Rebing\GraphQL\Tests\Support\Models\Comment;
use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;

class AlwaysRelationTest extends TestCaseDatabase
{
    use SqlAssertionTrait;

    /**
     * Once https://github.com/rebing/graphql-laravel/issues/369 is fixed,
     * the test needs to be changed, showing that it works.
     */
    public function testAlwaysSingleHasManyRelationField(): void
    {
        $user = factory(User::class)->create([
            'name' => 'User Name',
        ]);

        $post = factory(Post::class)->create([
            'user_id' => $user->id,
        ]);

        $comment = factory(Comment::class)->create([
            'post_id' => $post->id,
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

        $result = $this->graphql($query, [
            'expectErrors' => true,
        ]);

        $this->assertSqlQueries(<<<'SQL'
select "users"."id" from "users";
SQL
        );

//         $this->assertSqlQueries(<<<'SQL'
        // select "users"."id" from "users";
        // select "posts"."id", "posts"."title", "posts"."user_id" from "posts" where "posts"."user_id" in (?) order by "posts"."id" asc;
        // select "comments"."id", "comments"."post_id" from "comments" where "comments"."post_id" in (?) order by "comments"."id" asc;
        // SQL
        // );

        unset($result['errors'][0]['trace']);
        $expectedResult = [
            'errors' => [
                [
                    'debugMessage' => 'SQLSTATE[HY000]: General error: 1 no such column: posts.comments (SQL: select "posts"."id", "posts"."title", "posts"."user_id", "posts"."comments" from "posts" where "posts"."user_id" in (1) order by "posts"."id" asc)',
                    'message' => 'Internal server error',
                    'extensions' => [
                        'category' => 'internal',
                    ],
                    'locations' => [
                        [
                            'line' => 2,
                            'column' => 3,
                        ],
                    ],
                    'path' => [
                        'users',
                    ],
                ],
            ],
            'data' => [
                'users' => null,
            ],
        ];
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Once https://github.com/rebing/graphql-laravel/issues/369 is fixed,
     * the test needs to be changed, showing that it works.
     */
    public function testAlwaysSingleMorphRelationField(): void
    {
        $user = factory(User::class)->create([
            'name' => 'User Name',
        ]);

        $post = factory(Post::class)->create();

        $comment = factory(Comment::class)
            ->create([
                'post_id' => $post->id,
            ]);

        $postLike = new Like;
        $postLike->likable()->associate($post);
        $postLike->user()->associate($user);
        $postLike->save();

        $commentLike = new Like;
        $commentLike->likable()->associate($comment);
        $commentLike->user()->associate($user);
        $commentLike->save();

        $query = <<<'GRAQPHQL'
{
  users {
    name
    likes {
      id
    }
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->graphql($query, [
            'expectErrors' => true,
        ]);

        $this->assertSqlQueries(<<<'SQL'
select "users"."name", "users"."id" from "users";
SQL
        );
        // Expecting .._id and .._type to be selected.
        // select "likes"."id", "likes"."likable_id", "likes"."likable_type", "likes"."user_id" from "likes" where "likes"."user_id" in (?);

        unset($result['errors'][0]['trace']);
        $expectedResult = [
            'errors' => [
                [
                    'debugMessage' => 'SQLSTATE[HY000]: General error: 1 no such column: likes.likable (SQL: select "likes"."id", "likes"."user_id", "likes"."likable" from "likes" where "likes"."user_id" in (1))',
                    'message' => 'Internal server error',
                    'extensions' => [
                        'category' => 'internal',
                    ],
                    'locations' => [
                        [
                            'line' => 2,
                            'column' => 3,
                        ],
                    ],
                    'path' => [
                        'users',
                    ],
                ],
            ],
            'data' => [
                'users' => null,
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
            LikableInterfaceType::class,
            CommentType::class,
            LikeType::class,
            PostType::class,
            UserType::class,
        ]);
    }
}
