<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\ExecutionMiddleware;

use Closure;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Type\Schema;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Contracts\Config\Repository;
use Rebing\GraphQL\Support\OperationParams;

/**
 * Populates the GraphQL context value with the currently authenticated user
 * from the schema's configured guard.
 *
 * The guard is resolved in this order:
 * 1. `graphql.schemas.<name>.group_attributes.guard` — per-schema override.
 * 2. `graphql.route.group_attributes.guard` — global fallback (the same key
 *    Laravel route groups consume; see `src/routes.php`).
 * 3. The application's default guard (`null` passed to `Factory::guard()`).
 *
 * This keeps the GraphQL context consistent with the guard that authenticated
 * the route — important in multi-guard apps where the default guard
 * (typically `web`) and the schema's actual guard (e.g. `api`) differ.
 *
 * If the caller already supplied a `$contextValue`, it is left untouched.
 */
class AddAuthUserContextValueMiddleware extends AbstractExecutionMiddleware
{
    public function __construct(
        protected readonly Factory $auth,
        protected readonly Repository $config,
    ) {
    }

    public function handle(string $schemaName, Schema $schema, OperationParams $params, $rootValue, $contextValue, Closure $next): ExecutionResult
    {
        if (null === $contextValue) {
            $contextValue = $this->auth->guard($this->resolveGuard($schemaName))->user();
        }

        return $next($schemaName, $schema, $params, $rootValue, $contextValue);
    }

    protected function resolveGuard(string $schemaName): ?string
    {
        /** @var string|null $perSchema */
        $perSchema = $this->config->get("graphql.schemas.{$schemaName}.group_attributes.guard");

        if (null !== $perSchema) {
            return $perSchema;
        }

        /** @var string|null $global */
        $global = $this->config->get('graphql.route.group_attributes.guard');

        return $global;
    }
}
