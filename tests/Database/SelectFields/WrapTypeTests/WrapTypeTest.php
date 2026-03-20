<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\WrapTypeTests;

use Rebing\GraphQL\Tests\Support\Models\Comment;
use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;
use Rebing\GraphQL\Tests\TestCaseDatabase;

class WrapTypeTest extends TestCaseDatabase
{
    use SqlAssertionTrait;

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'query' => [
                WrapTypeQuery::class,
            ],
        ]);

        $app['config']->set('graphql.types', [
            CommentType::class,
            PostType::class,
        ]);
    }

    public function testWrapTypeSelectFieldsGeneratesCorrectSql(): void
    {
        Post::factory()->create([
            'title' => 'post 1',
        ]);

        $query = <<<'GRAPHQL'
{
  wrapTypeQuery {
    data {
      title
    }
    message
  }
}
GRAPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($query);

        $this->assertSqlQueries(
            <<<'SQL'
select count(*) as aggregate from "posts";
select "posts"."title", "posts"."id" from "posts" limit 1 offset 0;
SQL,
        );

        $expectedResult = [
            'data' => [
                'wrapTypeQuery' => [
                    'data' => [
                        [
                            'title' => 'post 1',
                        ],
                    ],
                    'message' => 'OK',
                ],
            ],
        ];
        self::assertEquals($expectedResult, $result);
    }

    public function testWrapTypeSelectFieldsWithRelations(): void
    {
        /** @var Post $post */
        $post = Post::factory()->create([
            'title' => 'post 1',
        ]);
        Comment::factory()->create([
            'title' => 'comment 1',
            'post_id' => $post->id,
        ]);

        $query = <<<'GRAPHQL'
{
  wrapTypeQuery {
    data {
      title
      comments {
        title
      }
    }
  }
}
GRAPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($query);

        $this->assertSqlQueries(
            <<<'SQL'
select count(*) as aggregate from "posts";
select "posts"."title", "posts"."id" from "posts" limit 1 offset 0;
select "comments"."title", "comments"."post_id", "comments"."id" from "comments" where "comments"."post_id" in (?) order by "comments"."id" asc;
SQL,
        );

        $expectedResult = [
            'data' => [
                'wrapTypeQuery' => [
                    'data' => [
                        [
                            'title' => 'post 1',
                            'comments' => [
                                [
                                    'title' => 'comment 1',
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
