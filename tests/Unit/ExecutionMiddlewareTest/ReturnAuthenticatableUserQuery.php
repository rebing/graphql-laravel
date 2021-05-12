<?php declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ExecutionMiddlewareTest;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\Type as GraphQLType;
use Illuminate\Auth\GenericUser;
use Rebing\GraphQL\Support\Query;

class ReturnAuthenticatableUserQuery extends Query
{
    /** @var array<string,mixed> */
    protected $attributes = [
        'name' => 'returnAuthenticatableUser',
    ];

    public function type(): GraphQLType
    {
        return Type::nonNull(Type::string());
    }

    /**
     * @param null $root
     * @param array<string,mixed> $args
     */
    public function resolve($root, array $args, GenericUser $context): string
    {
        return $context->getAuthIdentifierName();
    }
}
