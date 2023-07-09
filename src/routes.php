<?php

declare(strict_types = 1);

use Illuminate\Support\Facades\Route;
use Rebing\GraphQL\GraphQL;

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
], fn () => collect($schemasConfig)->map(fn ($schemaConfig, $schemaName) => array_merge(
    [GraphQL::parseRoute($schemaName, $schemaConfig, $routeConfig)],
    $schemaName === $defaultSchemaName ? [GraphQL::parseRoute($schemaName, $schemaConfig, $routeConfig, '')] : []
))->flatten(1));
