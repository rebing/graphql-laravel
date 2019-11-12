<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\InstantiableTypesTest;

use Carbon\Carbon;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class UserQuery extends Query
{
    protected $attributes = [
        'name' => 'postMessages',
    ];

    public function type(): Type
    {
        return GraphQL::type('UserType');
    }

    public function args(): array
    {
        return [];
    }

    public function resolve($root, $args)
    {
        return (object) [
            'id' => 1,
            'dateOfBirth' => Carbon::now()->addMonth()->startOfDay(),
            'created_at' => Carbon::now()->startOfDay(),
        ];
    }
}
