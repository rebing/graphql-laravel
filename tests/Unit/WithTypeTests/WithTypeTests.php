<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\WithTypeTests;

use Rebing\GraphQL\Tests\TestCase;
use Rebing\GraphQL\Tests\Unit\WithTypeTests\PostType;
use Rebing\GraphQL\Tests\Unit\WithTypeTests\PostMessagesQuery;
use Rebing\GraphQL\Tests\Unit\WithTypeTests\SimpleMessageType;

class WithTypeTests extends TestCase
{
    public function testPostMessagesQuery(): void
    {
        $query = '{
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
        }';

        $expectedResult = [
            'data' => [
                'postMessages' => [
                    'data' => [
                        'post_id',
                        'title',
                    ],
                    'messages' => [
                        '*' => [
                            'message',
                            'type',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->json('post', '/graphql', ['query' => $query])
            ->assertStatus(200)
            ->assertJsonStructure($expectedResult);
    }

    protected function getEnvironmentSetUp($app)
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
}
