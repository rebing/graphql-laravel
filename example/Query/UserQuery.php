<?php

declare(strict_types=1);

use GraphQL\GraphQL;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Models\User;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Support\SelectFields;

class UserQuery extends Query
{
    use Authenticate;

    protected $attributes = [
        'name' => 'Users',
    ];

    public function type(): Type
    {
        return GraphQL::type('user');
    }

    public function args(): array
    {
        return [
            'id' => [
                'name' => 'id',
                'type' => Type::int(),
            ],
        ];
    }

    public function resolve($root, $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        /** @var SelectFields $fields */
        $fields = $getSelectFields();
        $select = $fields->getSelect();
        $with = $fields->getRelations();

        return User::where('id', '=', $args['id'])->with($with)->select($select)->first();
    }
}
