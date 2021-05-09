<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ExecutionMiddlewareTest;

use Closure;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\Visitor;
use GraphQL\Type\Schema;
use Rebing\GraphQL\Support\ExecutionMiddleware\AbstractExecutionMiddleware;
use Rebing\GraphQL\Support\OperationParams;

class ChangeQueryArgTypeMiddleware extends AbstractExecutionMiddleware
{
    public function handle(string $schemaName, Schema $schema, OperationParams $params, $rootValue, $contextValue, Closure $next): ExecutionResult
    {
        $query = $params->getParsedQuery();

        Visitor::visit($query, [
            NodeKind::VARIABLE_DEFINITION => function ($node, $key, $parent, $path, $ancestors) {
                $node->type->name->value = 'Int';

                return $node;
            },
        ]);

        return $next($schemaName, $schema, $params, $rootValue, $contextValue);
    }
}
