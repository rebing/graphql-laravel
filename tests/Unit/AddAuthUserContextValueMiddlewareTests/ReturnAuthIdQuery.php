<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\AddAuthUserContextValueMiddlewareTests;

use GraphQL\Type\Definition\Type;
use Illuminate\Contracts\Auth\Authenticatable;
use Rebing\GraphQL\Support\Query;

/**
 * Returns the auth identifier injected as the GraphQL context value, so a
 * test can verify which user the context contains. Returns "no-user" when
 * the context is not an Authenticatable.
 */
class ReturnAuthIdQuery extends Query
{
    /** @var array<string,string> */
    protected $attributes = [
        'name' => 'returnAuthId',
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::string());
    }

    /**
     * @param mixed $root
     * @param array<string,mixed> $args
     * @param mixed $context
     */
    public function resolve($root, array $args, $context): string
    {
        if (!$context instanceof Authenticatable) {
            return 'no-user';
        }

        return (string) $context->getAuthIdentifier();
    }
}
