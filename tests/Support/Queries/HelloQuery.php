<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Support\Queries;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Query;

class HelloQuery extends Query
{
    /** @var string */
    public const NAME = 'hello';

    protected $attributes = [
        'name' => self::NAME,
    ];

    public function type(): Type
    {
        return Type::string();
    }

    public function resolve($root, $args)
    {
        return 'Hello';
    }
}
