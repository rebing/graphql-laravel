<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\AlwaysRelationTests;

use Rebing\GraphQL\Tests\Support\Models\Comment;
use Rebing\GraphQL\Tests\Support\Models\Like;
use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\Support\Models\User;
use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;
use Rebing\GraphQL\Tests\TestCaseDatabase;

class AlwaysRelationTest extends TestCaseDatabase
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
            LikableInterfaceType::class,
            CommentType::class,
            LikeType::class,
            PostType::class,
            UserType::class,
        ]);
    }

    public function testAlwaysSingleHasManyRelationField(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create([
            'name' => 'User Name',
        ]);

        /** @var Post $post */
        $post = factory(Post::class)->create([
            'user_id' => $user->id,
        ]);

        factory(Comment::class)->create([
            'post_id' => $post->id,
        ]);

        $query = <<<'GRAQPHQL'
{
  users {
    id
    posts {
      id
      title
      comments {
          id
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
        select "comments"."id", "comments"."post_id" from "comments" where "comments"."post_id" in (?) order by "comments"."id" asc;
        SQL
        );

        $expectedResult = [
            'data' => [
                'users' => [
                    [
                        'id' => $user->id,
                        'posts' => [
                            [
                                'id' => 1,
                                'title' => $post->title,
                                'comments' => [
                                    [
                                        'id' => 1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        self::assertEquals($expectedResult, $result);
    }

    public function testAlwaysSingleMorphRelationField(): void
    {
        $user = factory(User::class)->create([
            'name' => 'User Name',
        ]);

        /** @var Post $post */
        $post = factory(Post::class)->create();

        $comment = factory(Comment::class)
            ->create([
                'post_id' => $post->id,
            ]);

        $postLike = new Like();
        $postLike->likable()->associate($post);
        $postLike->user()->associate($user);
        $postLike->save();

        $commentLike = new Like();
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

        $result = $this->httpGraphql($query);

        $this->assertSqlQueries(
            <<<'SQL'
select "users"."name", "users"."id" from "users";
select "likes"."id", "likes"."user_id" from "likes" where "likes"."user_id" in (?);
SQL
        );

        $expectedResult = [
            'data' => [
                'users' => [
                    [
                        'name' => $user->name,
                        'likes' => [
                            [
                                'id' => '1',
                            ],
                            [
                                'id' => '2',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        self::assertEquals($expectedResult, $result);
    }
}
