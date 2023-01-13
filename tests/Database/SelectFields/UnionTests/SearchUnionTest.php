<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\UnionTests;

use Rebing\GraphQL\Tests\Support\Models\Comment;
use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\TestCaseDatabase;

class SearchUnionTest extends TestCaseDatabase
{
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

    public function testCustomQueryIsExecutedUsingUnionTypeOnQuery(): void
    {
        /** @var Post $post */
        $post = factory(Post::class)->create();
        /** @var Comment $comment1 */
        $comment1 = factory(Comment::class)->create(['post_id' => $post->id, 'title' => 'lorem']);
        /** @var Comment $comment2 */
        $comment2 = factory(Comment::class)->create(['post_id' => $post->id, 'title' => 'ipsum']);

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

        $result = $this->httpGraphql($query, [
            'variables' => ['id' => (string)$post->id],
        ]);

        $expectedResult = [
            'data' => [
                'searchQuery' => [
                    'id' => (string)$post->id,
                    'comments' => [
                        ['id' => $comment1->id],
                    ],
                ],
            ],
        ];
        self::assertEquals($expectedResult, $result);
    }
}
