<?php

declare(strict_types = 1);

use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Routing\Router;
use Rebing\GraphQL\GraphQL;
use Rebing\GraphQL\GraphQLController;

/** @var Repository $config */
$config = Container::getInstance()->make(Repository::class);

$routeConfig = $config->get('graphql.route');

if ($routeConfig) {
    /** @var Router $router */
    $router = app('router');

    $routeGroupAttributes = array_merge(
        [
            'prefix' => $routeConfig['prefix'] ?? 'graphql',
            'middleware' => $routeConfig['middleware'] ?? [],
        ],
        $routeConfig['group_attributes'] ?? []
    );

    $router->group(
        $routeGroupAttributes,
        function (Router $router) use ($config, $routeConfig): void {
            $schemas = GraphQL::getNormalizedSchemasConfiguration();
            $defaultSchema = $config->get('graphql.default_schema', 'default');

            foreach ($schemas as $schemaName => $schemaConfig) {
                $method = $schemaConfig['method'] ?? ['GET', 'POST'];
                $actions = array_filter([
                    'uses' => $schemaConfig['controller'] ?? $routeConfig['controller'] ?? GraphQLController::class . '@query',
                    'middleware' => $schemaConfig['middleware'] ?? $routeConfig['middleware'] ?? null,
                ]);

                // Add route for each schema…
                $router->addRoute(
                    $method,
                    $schemaName,
                    $actions + ['as' => "graphql.$schemaName"]
                );

                // … and the default schema against the group itself
                if ($schemaName === $defaultSchema) {
                    $router->addRoute(
                        $method,
                        '',
                        $actions + ['as' => 'graphql']
                    );
                }
            }
        }
    );
}

if ($config->get('graphql.graphiql.display', true)) {
    /** @var Router $router */
    $router = app('router');
    $graphiqlConfig = $config->get('graphql.graphiql');

    $router->group(
        [
            'prefix' => $graphiqlConfig['prefix'] ?? 'graphiql',
            'middleware' => $graphiqlConfig['middleware'] ?? [],
        ],
        function (Router $router) use ($config, $graphiqlConfig): void {
            $actions = [
                'uses' => $graphiqlConfig['controller'] ?? GraphQLController::class . '@graphiql',
            ];

            // A graphiql route for each schema…
            /** @var string $schemaName */
            foreach (array_keys($config->get('graphql.schemas', [])) as $schemaName) {
                $router->get(
                    $schemaName,
                    $actions + ['as' => "graphql.graphiql.$schemaName"]
                );
            }

            // … and one for the default schema against the group itself
            $router->get('/', $actions + ['as' => 'graphql.graphiql']);
        }
    );
}
