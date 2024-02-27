<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\DepthTests;

use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\Support\Models\User;
use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;
use Rebing\GraphQL\Tests\TestCaseDatabase;

class DepthTest extends TestCaseDatabase
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

    public function testDefaultDepthExceeded(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        Post::factory()->create([
            'user_id' => $user->id,
        ]);

        $graphql = <<<'GRAQPHQL'
{
  users {
    id
    posts {
      id
      user {
        id
        posts {
          id
          user {
            id
            posts {
              id
              user {
                id
              }
            }
          }
        }
      }
    }
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($graphql);

        self::assertArrayNotHasKey('errors', $result);
    }
}
