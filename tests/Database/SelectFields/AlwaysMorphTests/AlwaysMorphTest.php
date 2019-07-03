<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\AlwaysMorphTests;

use Rebing\GraphQL\Tests\TestCaseDatabase;
use Rebing\GraphQL\Tests\Support\Models\Like;
use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\Support\Models\User;
use Rebing\GraphQL\Tests\Support\Models\Comment;
use Rebing\GraphQL\Tests\Support\Types\LikeType;
use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;

class AlwaysMorphTest extends TestCaseDatabase
{
    use SqlAssertionTrait;

    public function testAlwaysMorphSingleField(): void
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

        $result = $this->graphql($query);

        // Expecting .._id and .._type to be selected.
        $this->assertSqlQueries(<<<'SQL'
select "users"."name", "users"."id" from "users";
select "likes"."id", "likes"."likable_id", "likes"."likable_type", "likes"."user_id" from "likes" where "likes"."user_id" in (?);
SQL
        );

        $expectedResult = [
            'data' => [
                'users' => [
                    [
                        'name' => 'User Name',
                        'likes' => [
                            [
                                "id" => $post->id,
                            ],
                            [
                                "id" => $comment->id,
                            ],
                        ]
                    ]
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
            LikableInterfaceType::class,
            CommentType::class,
            LikeType::class,
            PostType::class,
            UserType::class,
        ]);
    }
}
