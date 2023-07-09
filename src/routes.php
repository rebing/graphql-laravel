<?php

declare(strict_types = 1);

use Illuminate\Support\Facades\Route;
use Rebing\GraphQL\GraphQL;
use Rebing\GraphQL\GraphQLController;
use Rebing\GraphQL\GraphQLHttpMiddleware;
use Rebing\GraphQL\Support\Contracts\ConfigConvertible;

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
], function () use (&$routeConfig, $defaultSchemaName): void {
    foreach (config('graphql.schemas', []) as $schemaName => $schemaConfig) {
        if (\is_string($schemaConfig) && class_exists($schemaConfig)) {
            $classSchema = (new $schemaConfig());

            if ($classSchema instanceof ConfigConvertible) {
                $schemaConfig = $classSchema->toConfig();
            }
        }

        if ($defaultSchemaName === $schemaName) {
            registerGraphQLRoute($schemaName, $schemaConfig, $routeConfig, '');
        }
        registerGraphQLRoute($schemaName, $schemaConfig, $routeConfig);
    }
});

//
///** @var Repository $config */
//$config = Container::getInstance()->make(Repository::class);
//
//$routeConfig = $config->get('graphql.route');
//
//if ($routeConfig) {
//    /** @var Router $router */
//    $router = app('router');
//
//    $routeGroupAttributes = array_merge(
//        [
//            'prefix' => $routeConfig['prefix'] ?? 'graphql',
//            'middleware' => $routeConfig['middleware'] ?? [],
//        ],
//        $routeConfig['group_attributes'] ?? []
//    );
//
//    $router->group(
//        $routeGroupAttributes,
//        function (Router $router) use ($config, $routeConfig): void {
//            $schemas = GraphQL::getNormalizedSchemasConfiguration();
//            $defaultSchema = $config->get('graphql.default_schema', 'default');
//
//            foreach ($schemas as $schemaName => $schemaConfig) {
//                $method = $schemaConfig['method'] ?? ['GET', 'POST'];
//                $actions = array_filter([
//                    'uses' => $schemaConfig['controller'] ?? $routeConfig['controller'] ?? GraphQLController::class . '@query',
//                    'middleware' => $schemaConfig['middleware'] ?? $routeConfig['middleware'] ?? null,
//                ]);
//
//                // Support array syntax: `[Some::class, 'method']`
//                if (\is_array($actions['uses']) && isset($actions['uses'][0], $actions['uses'][1])) {
//                    $actions['uses'] = $actions['uses'][0] . '@' . $actions['uses'][1];
//                }
//
//                // Add route for each schema…
//                $router->addRoute(
//                    $method,
//                    $schemaName,
//                    $actions + ['as' => "graphql.$schemaName"]
//                );
//
//                // … and the default schema against the group itself
//                if ($schemaName === $defaultSchema) {
//                    $router->addRoute(
//                        $method,
//                        '',
//                        $actions + ['as' => 'graphql']
//                    );
//                }
//            }
//        }
//    );
//}
