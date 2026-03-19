<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\UnionTests;

use Rebing\GraphQL\Tests\Support\Models\Comment;
use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;
use Rebing\GraphQL\Tests\TestCaseDatabase;

class SearchUnionTest extends TestCaseDatabase
{
    use SqlAssertionTrait;
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'query' => [
                SearchQuery::class,
            ],
        ]);

        $app['config']->set('graphql.types', [
            CommentType::class,
            PostType::class,
            SearchUnionType::class,
        ]);
    }

    /**
     * Test that the custom `'query'` on the `comments` field in
     * `\Rebing\GraphQL\Tests\Database\SelectFields\UnionTests\PostType::fields`
     * is properly applied when querying through a UnionType, so only the
     * comment matching `lorem` is returned.
     *
     * @link https://github.com/rebing/graphql-laravel/issues/900
     */
    public function testCustomQueryIsExecutedUsingUnionTypeOnQuery(): void
    {
        /** @var Post $post */
        $post = Post::factory()->create();
        /** @var Comment $comment1 */
        $comment1 = Comment::factory()->create(['post_id' => $post->id, 'title' => 'lorem']);
        /** @var Comment $comment2 */
        $comment2 = Comment::factory()->create(['post_id' => $post->id, 'title' => 'ipsum']);

        $query = <<<'GRAQPHQL'
query ($id: String!) {
  searchQuery(id: $id) {
    ... on Post {
        id
        comments {
            id
        }
    }
    ... on Comment {
        id
    }
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($query, [
            'variables' => ['id' => (string) $post->id],
        ]);

        $this->assertSqlQueries(
            <<<'SQL'
select * from "posts" where "id" = ? limit 1;
select "comments"."id", "comments"."post_id" from "comments" where "comments"."post_id" in (?) and "title" like ? order by "comments"."id" asc;
SQL,
        );

        $expectedResult = [
            'data' => [
                'searchQuery' => [
                    'id' => (string) $post->id,
                    'comments' => [
                        ['id' => $comment1->id],
                    ],
                ],
            ],
        ];
        self::assertEquals($expectedResult, $result);
    }

    /**
     * Test that querying scalar fields through a union type (without relations)
     * results in a single SELECT * query (since union types always select all
     * columns) and no eager-load queries.
     */
    public function testScalarFieldsReturnedThroughUnionWithoutRelations(): void
    {
        /** @var Post $post */
        $post = Post::factory()->create();
        /** @var Comment $comment */
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'title' => 'test comment',
        ]);

        $query = <<<'GRAPHQL'
query ($id: String!) {
  searchQuery(id: $id) {
    ... on Comment {
        id
        title
    }
  }
}
GRAPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($query, [
            'variables' => ['id' => 'comment:' . $comment->id],
        ]);

        $this->assertSqlQueries(
            <<<'SQL'
select * from "comments" where "id" = ? limit 1;
SQL,
        );

        $expectedResult = [
            'data' => [
                'searchQuery' => [
                    'id' => (string) $comment->id,
                    'title' => 'test comment',
                ],
            ],
        ];
        self::assertSame($expectedResult, $result);
    }

    /**
     * Test that a BelongsTo relation without a custom `query` callback is
     * properly eager-loaded when querying through a UnionType.
     *
     * The `post` field on CommentType has `is_relation => true` but no
     * `query` callback, exercising the null `$customQuery` fallback path
     * in `handleUnionFields()`.
     */
    public function testRelationWithoutCustomQueryThroughUnion(): void
    {
        /** @var Post $post */
        $post = Post::factory()->create(['title' => 'parent post']);
        /** @var Comment $comment */
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'title' => 'child comment',
        ]);

        $query = <<<'GRAPHQL'
query ($id: String!) {
  searchQuery(id: $id) {
    ... on Comment {
        id
        post {
            id
            title
        }
    }
  }
}
GRAPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($query, [
            'variables' => ['id' => 'comment:' . $comment->id],
        ]);

        $this->assertSqlQueries(
            <<<'SQL'
select * from "comments" where "id" = ? limit 1;
select "posts"."id", "posts"."title" from "posts" where "posts"."id" in (?);
SQL,
        );

        $expectedResult = [
            'data' => [
                'searchQuery' => [
                    'id' => (string) $comment->id,
                    'post' => [
                        'id' => (string) $post->id,
                        'title' => 'parent post',
                    ],
                ],
            ],
        ];
        self::assertSame($expectedResult, $result);
    }

    /**
     * Test querying a field that exists only on the second union member type
     * (Comment has `post`, but Post does not). This exercises
     * `findFieldInConcreteUnionTypes()` which must skip Post (throws
     * InvariantViolation) and find the field definition on Comment.
     */
    public function testFieldUniqueToSecondUnionMemberType(): void
    {
        /** @var Post $post */
        $post = Post::factory()->create(['title' => 'the post']);
        /** @var Comment $comment */
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'title' => 'the comment',
        ]);

        $query = <<<'GRAPHQL'
query ($id: String!) {
  searchQuery(id: $id) {
    ... on Comment {
        id
        post {
            id
        }
    }
  }
}
GRAPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($query, [
            'variables' => ['id' => 'comment:' . $comment->id],
        ]);

        $this->assertSqlQueries(
            <<<'SQL'
select * from "comments" where "id" = ? limit 1;
select "posts"."id" from "posts" where "posts"."id" in (?);
SQL,
        );

        $expectedResult = [
            'data' => [
                'searchQuery' => [
                    'id' => (string) $comment->id,
                    'post' => [
                        'id' => (string) $post->id,
                    ],
                ],
            ],
        ];
        self::assertSame($expectedResult, $result);
    }

    /**
     * Test that both inline fragments can be present in a single query
     * when the non-matching fragment only has scalar fields (no relations).
     *
     * When resolved with a Comment id (prefixed `comment:`), the Comment
     * fragment matches and eager-loads the `post` relation, while the Post
     * fragment only requests scalars. This validates that SelectFields
     * correctly processes both fragments without errors and that the
     * relation from the Comment fragment is properly eager-loaded.
     */
    public function testBothFragmentsWithRelationOnOneAndScalarsOnOther(): void
    {
        /** @var Post $post */
        $post = Post::factory()->create(['title' => 'the post']);
        /** @var Comment $comment */
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'title' => 'the comment',
        ]);

        $query = <<<'GRAPHQL'
query ($id: String!) {
  searchQuery(id: $id) {
    ... on Post {
        id
        title
    }
    ... on Comment {
        id
        post {
            id
        }
    }
  }
}
GRAPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($query, [
            'variables' => ['id' => 'comment:' . $comment->id],
        ]);

        $this->assertSqlQueries(
            <<<'SQL'
select * from "comments" where "id" = ? limit 1;
select "posts"."id" from "posts" where "posts"."id" in (?);
SQL,
        );

        $expectedResult = [
            'data' => [
                'searchQuery' => [
                    'id' => (string) $comment->id,
                    'post' => [
                        'id' => (string) $post->id,
                    ],
                ],
            ],
        ];
        self::assertSame($expectedResult, $result);
    }
}
