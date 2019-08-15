<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\CustomContext;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Query;

class QueryReceivingRequestInContextQuery extends Query
{
    protected $attributes = [
        'name' => 'queryReceivingRequestInContext',
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::string());
    }

    public function resolve($root, $args, Context $ctx): string
    {
        return 'The URL used for the GraphQL request: '.$ctx->getRequest()->url();
    }
}
