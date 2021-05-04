<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ExecutionMiddlewareTest;

use Closure;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\Parser;
use GraphQL\Language\Visitor;
use Rebing\GraphQL\Support\ExecutionMiddleware\ExecutionMiddleware;

class ChangeQueryArgTypeMiddleware extends ExecutionMiddleware
{
    /**
     * @inheritdoc
     */
    public function handle($query, $args, array $opts, Closure $next)
    {
        $query = Parser::parse($query);

        Visitor::visit($query, [
            NodeKind::VARIABLE_DEFINITION => function ($node, $key, $parent, $path, $ancestors) {
                $node->type->name->value = 'Int';

                return $node;
            },
        ]);

        return $next($query, $args, $opts);
    }
}
