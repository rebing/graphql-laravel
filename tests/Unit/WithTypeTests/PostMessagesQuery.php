<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\WithTypeTests;

use GraphQL\Type\Definition\Type;
use Illuminate\Support\Collection;
use Rebing\GraphQL\Support\Query;

class PostMessagesQuery extends Query
{
    protected $attributes = [
        'name' => 'postMessages',
    ];

    public function type(): Type
    {
        return MessageWrapper::type('PostType');
    }

    public function args(): array
    {
        return [];
    }

    public function resolve($root, $args)
    {
        return [
            'data' => [
                'post_id' => 1,
                'title' => 'This is the title post',
            ],
            'messages' => new Collection([
                new SimpleMessage('Congratulations, the post was found'),
                new SimpleMessage('This post cannot be edited", "warning'),
            ]),
        ];
    }
}
