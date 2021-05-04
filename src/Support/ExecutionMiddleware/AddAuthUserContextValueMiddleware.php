<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\ExecutionMiddleware;

use Closure;
use Illuminate\Container\Container;
use Rebing\GraphQL\Support\OperationParams;

class AddAuthUserContextValueMiddleware extends AbstractExecutionMiddleware
{
    /**
     * @inheritdoc
     */
    public function handle(string $schemaName, OperationParams $params, $rootValue, $contextValue, Closure $next)
    {
        if (null === $contextValue) {
            $contextValue = Container::getInstance()->make('auth')->user();
        }

        return $next($schemaName, $params, $rootValue, $contextValue);
    }
}
