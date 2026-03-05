<?php declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\SearchInputTest;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class UserQuery extends Query
{
    protected $attributes = [
        'name' => 'user',
        'description' => 'Find a user by search criteria',
    ];

    public function type(): Type
    {
        return Type::string();
    }

    public function args(): array
    {
        return [
            'search' => [
                'type' => Type::nonNull(GraphQL::type('SearchInput')),
                'description' => 'Search criteria (exactly one field required)',
            ],
        ];
    }

    /**
     * @param array<string, array<string, string>> $args
     */
    public function resolve(?Query $root, array $args): ?string
    {
        return array_key_first($args['search']);
    }
}
