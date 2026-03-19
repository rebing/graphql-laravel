<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\AliasedRelationArgTests;

use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\Support\Models\User;
use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;
use Rebing\GraphQL\Tests\TestCaseDatabase;

/**
 * Documents the current (incorrect) behaviour for
 * https://github.com/rebing/graphql-laravel/issues/604
 *
 * When the same relationship field is queried via GraphQL aliases with
 * different arguments, SelectFields should apply the correct arguments
 * for each alias independently. Currently it does not — QueryPlan
 * keys fields by name (not alias), so the last alias's args win and
 * a single eager load is shared across all aliases.
 *
 * These tests assert the **current buggy behaviour** so the suite
 * stays green. Once the bug is fixed, update the assertions to match
 * the correct expected output described in the inline comments.
 */
class AliasedRelationArgTest extends TestCaseDatabase
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

    /**
     * Core reproduction of issue #604.
     *
     * Two aliases of the same relationship field with different `flag`
     * arguments should each return only the posts matching their own
     * argument value. Currently both aliases return the same result
     * (the last alias's args win).
     */
    public function testAliasedRelationWithDifferentArgs(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Post $flaggedPost */
        $flaggedPost = Post::factory()->create([
            'flag' => true,
            'user_id' => $user->id,
        ]);

        /** @var Post $unflaggedPost */
        $unflaggedPost = Post::factory()->create([
            'flag' => false,
            'user_id' => $user->id,
        ]);

        $graphql = <<<'GRAQPHQL'
{
  users(select: true, with: true) {
    id
    name
    flaggedPosts: posts(flag: true) {
      id
      title
    }
    unflaggedPosts: posts(flag: false) {
      id
      title
    }
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($graphql);

        // Bug https://github.com/rebing/graphql-laravel/issues/604
        //
        // Correct behaviour: each alias should apply its own `flag` argument
        // independently, so `flaggedPosts` returns only flag=true posts and
        // `unflaggedPosts` returns only flag=false posts.
        //
        // Actual behaviour: QueryPlan keys by field name (not alias), so the
        // last alias's args overwrite earlier ones. Here `unflaggedPosts`
        // (flag=false) appears last, causing a single eager load with
        // `WHERE posts.flag = false`. Both aliases receive the same
        // (incorrectly filtered) result.
        $expectedResult = [
            'data' => [
                'users' => [
                    [
                        'id' => (string) $user->id,
                        'name' => $user->name,
                        'flaggedPosts' => [
                            [
                                'id' => (string) $unflaggedPost->id,
                                'title' => $unflaggedPost->title,
                            ],
                        ],
                        'unflaggedPosts' => [
                            [
                                'id' => (string) $unflaggedPost->id,
                                'title' => $unflaggedPost->title,
                            ],
                        ],
                    ],
                ],
            ],
        ];
        self::assertEquals($expectedResult, $result);
    }

    /**
     * Variant: one alias with args alongside the un-aliased version
     * without args.
     *
     * Correct behaviour: the un-aliased field should return all posts,
     * while the aliased field should return only the filtered subset.
     */
    public function testAliasedRelationAlongsideUnaliased(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Post $flaggedPost */
        $flaggedPost = Post::factory()->create([
            'flag' => true,
            'user_id' => $user->id,
        ]);

        /** @var Post $unflaggedPost */
        $unflaggedPost = Post::factory()->create([
            'flag' => false,
            'user_id' => $user->id,
        ]);

        $graphql = <<<'GRAQPHQL'
{
  users(select: true, with: true) {
    id
    name
    posts {
      id
      title
    }
    flaggedPosts: posts(flag: true) {
      id
      title
    }
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($graphql);

        // Bug https://github.com/rebing/graphql-laravel/issues/604
        //
        // Correct behaviour: `posts` (no args) returns all posts,
        // `flaggedPosts` (flag=true) returns only flagged posts.
        //
        // Actual behaviour: the last alias's args (flag=true) overwrite
        // the un-aliased field's empty args, causing a single eager load
        // with `WHERE posts.flag = true`. Both aliases receive only
        // the flagged post.
        $expectedResult = [
            'data' => [
                'users' => [
                    [
                        'id' => (string) $user->id,
                        'name' => $user->name,
                        'posts' => [
                            [
                                'id' => (string) $flaggedPost->id,
                                'title' => $flaggedPost->title,
                            ],
                        ],
                        'flaggedPosts' => [
                            [
                                'id' => (string) $flaggedPost->id,
                                'title' => $flaggedPost->title,
                            ],
                        ],
                    ],
                ],
            ],
        ];
        self::assertEquals($expectedResult, $result);
    }
}
