<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\InterfaceTests;

use PHPUnit\Framework\ExpectationFailedException;
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

        $this->assertSqlQueries(<<<'SQL'
select "title" from "posts";
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
        $this->assertSame($expectedResult, $result);
    }

    public function testGeneratedRelationSqlQuery(): void
    {
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

        $this->assertSqlQueries(<<<'SQL'
select "id", "title" from "posts";
select * from "comments" where "comments"."post_id" = ? and "comments"."post_id" is not null order by "comments"."id" asc;
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
        $this->assertSame($expectedResult, $result);
    }

    public function testGeneratedInterfaceFieldSqlQuery()
    {
        $this->expectException(ExpectationFailedException::class);
        $post = factory(Post::class)
            ->create([
                'title' => 'Title of the post',
            ]);
        factory(Comment::class)
            ->create([
                'title' => 'Title of the comment',
                'post_id' => $post->id,
            ]);

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

        $this->assertSqlQueries(<<<'SQL'
select "users"."id" from "users";
select "likes"."likable_id", "likes"."likable_type", "likes"."user_id", "likes"."id" from "likes" where "likes"."user_id" in (?);
select "id", "name" from "posts" where "posts"."id" in (?);
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
        $this->assertSame($expectedResult, $result);
    }

    protected function getEnvironmentSetUp($app)
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
