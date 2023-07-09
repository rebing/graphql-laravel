<?php

declare(strict_types = 1);

use Illuminate\Support\Facades\Route;
use Rebing\GraphQL\GraphQL;
use Rebing\GraphQL\GraphQLController;
use Rebing\GraphQL\GraphQLHttpMiddleware;

if (!\function_exists('registerGraphQLRoute')) {
    function registerGraphQLRoute($schemaName, $schemaConfig, $routeConfig, $alias = null): void
    {
        if (null !== $alias) {
            $routeName = $alias ? ".$alias" : '';
        } else {
            $routeName = $schemaName ? ".$schemaName" : '';
        }

        Route::match(
            $schemaConfig['method'] ?? ['GET', 'POST'],
            $alias ?? $schemaName,
            $schemaConfig['controller'] ?? $routeConfig['controller'] ?? [GraphQLController::class, 'query'],
        )->middleware(array_merge(
            [GraphQLHttpMiddleware::class . ":$schemaName"],
            $schemaConfig['middleware'] ?? []
        ))->name($routeName);
    }
}

$routeConfig = config('graphql.route');

if (empty($routeConfig)) {
    return;
}

$defaultSchemaName = config('graphql.default_schema', 'default');
$schemasConfig = GraphQL::getNormalizedSchemasConfiguration();

Route::group([
    'prefix' => $routeConfig['prefix'] ?? 'graphql',
    'middleware' => $routeConfig['middleware'] ?? [],
    'as' => 'graphql',
    ...$routeConfig['group_attributes'] ?? [],
], function () use (&$routeConfig, &$defaultSchemaName, &$schemasConfig): void {
    foreach ($schemasConfig as $schemaName => $schemaConfig) {
        if ($defaultSchemaName === $schemaName) {
            registerGraphQLRoute($schemaName, $schemaConfig, $routeConfig, '');
        }
        registerGraphQLRoute($schemaName, $schemaConfig, $routeConfig);
    }
});
