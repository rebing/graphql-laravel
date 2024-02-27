<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\WithTypeTests;

use Rebing\GraphQL\Tests\TestCase;

class WithTypeTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'query' => [
                PostMessagesQuery::class,
            ],
        ]);
        $app['config']->set('graphql.types', [
            SimpleMessageType::class,
            PostType::class,
        ]);
    }

    public function testPostMessagesQuery(): void
    {
        $query = <<<'GRAQPHQL'
{
    postMessages {
        data {
            post_id
            title
        }
        messages {
            message
            type
        }
    }
}
GRAQPHQL;

        $result = $this->httpGraphql($query);

        $expectedResult = [
            'data' => [
                'postMessages' => [
                    'data' => [
                        'post_id' => 1,
                        'title' => 'This is the title post',
                    ],
                    'messages' => [
                        [
                            'message' => 'Congratulations, the post was found',
                            'type' => 'success',
                        ],
                        [
                            'message' => 'This post cannot be edited", "warning',
                            'type' => 'success',
                        ],
                    ],
                ],
            ],
        ];
        self::assertSame($expectedResult, $result);
    }
}
