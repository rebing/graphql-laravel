<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\CustomContext;

use Illuminate\Http\Request;
use Rebing\GraphQL\GraphQLController;

class CustomGraphQLController extends GraphQLController
{
    protected function queryContext(string $query, ?array $params, string $schema, Request $request)
    {
        return new Context($request);
    }
}
