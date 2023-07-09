<?php

declare(strict_types = 1);

use Illuminate\Support\Facades\Route;
use Rebing\GraphQL\GraphQL;

$routeConfig = config('graphql.route');

if (empty($routeConfig)) {
    return;
}

$defaultSchemaName = config('graphql.default_schema', 'default');
Route::group([
    'prefix' => $routeConfig['prefix'] ?? 'graphql',
    'middleware' => $routeConfig['middleware'] ?? [],
    'as' => 'graphql',
    ...$routeConfig['group_attributes'] ?? [],
], function () use (&$defaultSchemaName, &$routeConfig): void {
    foreach (GraphQL::getNormalizedSchemasConfiguration() as $schemaName => $schemaConfig) {
        GraphQL::parseRoute($schemaName, $schemaConfig, $routeConfig);

        if ($schemaName === $defaultSchemaName) {
            GraphQL::parseRoute($schemaName, $schemaConfig, $routeConfig, '');
        }
    }
});
