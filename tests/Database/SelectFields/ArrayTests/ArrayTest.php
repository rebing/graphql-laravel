<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\ArrayTests;

use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\TestCaseDatabase;

class ArrayTest extends TestCaseDatabase
{
    public function testArrayFieldRetrieved(): void
    {
        $properties = [
            [
                'name' => '111',
                'title' => '222',
            ],
            [
                'name' => '333',
                'title' => '444',
            ],
        ];

        /** @var Post $post */
        $post = factory(Post::class)->create([
            'properties' => $properties,
        ]);

        $query = <<<'GRAQPHQL'
{
  arrayQuery {
    id
    properties {
      title
    }
  }
}
GRAQPHQL;

        $result = $this->graphql($query);

        $expectedResult = [
            'data' => [
                'arrayQuery' => [
                    [
                        'id' => $post->id,
                        'properties' => [
                            [
                                'title' => '222',
                            ],
                            [
                                'title' => '444',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        self::assertEquals($expectedResult, $result);
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'query' => [
                ArrayQuery::class,
            ],
        ]);

        $app['config']->set('graphql.types', [
            PostType::class,
            PropertyType::class,
        ]);
    }
}
