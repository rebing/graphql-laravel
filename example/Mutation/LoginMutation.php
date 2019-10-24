<?php

declare(strict_types=1);

use GraphQL\GraphQL;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Rebing\GraphQL\Support\Mutation;
use Rebing\Services\Auth\UserLoginService; // not included in this project

class LoginMutation extends Mutation
{
    protected $attributes = [
        'name' => 'Login',
        'description' => 'Log the user in by email',
    ];

    public function type(): Type
    {
        return GraphQL::type('user');
    }

    public function args(): array
    {
        return [
            'email' => [
                'name' => 'email',
                'type' => Type::nonNull(Type::string()),
                'rules' => ['required', 'email'],
            ],
            'password' => [
                'name' => 'password',
                'type' => Type::nonNull(Type::string()),
                'rules' => ['required', 'string'],
            ],
            'remember_me' => [
                'name' => 'remember_me',
                'type' => Type::boolean(),
                'rules' => ['boolean'],
            ],
        ];
    }

    public function resolve($root, $args)
    {
        $loginService = new UserLoginService();
        $user = $loginService->doLogin($args['email'], $args['password'], Arr::get($args, 'remember_me'));

        return $user;
    }
}
