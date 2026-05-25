<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\ExecutionMiddleware;

use Closure;
use GraphQL\Error\Error;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Server\Exception\GetMethodSupportsOnlyQueryOperation;
use GraphQL\Type\Schema;
use GraphQL\Utils\AST;
use Rebing\GraphQL\Support\OperationParams;

/**
 * Rejects non-`query` operations submitted on read-only HTTP requests (i.e. GET).
 *
 * This middleware is opt-in: register it via the `execution_middleware` config
 * (globally or per-schema) whenever any schema permits the GET method. Without
 * it, mutations and subscriptions can be executed via GET, which leaks
 * arguments into URLs, server logs and CDN caches.
 *
 * Ordering: it must be listed AFTER `AutomaticPersistedQueriesMiddleware` when
 * APQ is enabled, because APQ materialises `$params->query` from cache.
 * Running this middleware first against an APQ-only request would cause
 * `OperationParams::getParsedQuery()` to throw "No GraphQL query available".
 *
 * It runs before `GraphqlExecutionMiddleware`; that ordering is enforced by
 * the framework, which always appends `GraphqlExecutionMiddleware` last (see
 * `GraphQL::appendGraphqlExecutionMiddleware()`).
 */
class ReadOnlyOperationMiddleware extends AbstractExecutionMiddleware
{
    public function handle(string $schemaName, Schema $schema, OperationParams $params, $rootValue, $contextValue, Closure $next): ExecutionResult
    {
        if (!$params->isReadOnly()) {
            return $next($schemaName, $schema, $params, $rootValue, $contextValue);
        }

        $operationAST = AST::getOperationAST($params->getParsedQuery(), $params->operation);

        // If the operation cannot be located (ambiguous or missing) defer to
        // webonyx's own `FailedToDetermineOperationType` handling rather than
        // pre-empting it here.
        if (null !== $operationAST && 'query' !== $operationAST->operation) {
            return new ExecutionResult(null, [
                Error::createLocatedError(
                    new GetMethodSupportsOnlyQueryOperation('GET supports only query operation'),
                ),
            ]);
        }

        return $next($schemaName, $schema, $params, $rootValue, $contextValue);
    }
}
