<?php

declare(strict_types = 1);

use Illuminate\Routing\Router;
use Rebing\GraphQL\GraphQL;
use Rebing\GraphQL\GraphQLController;

$routeConfig = config('graphql.route');

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
        function (Router $router) use ($routeConfig): void {
            $schemas = GraphQL::getNormalizedSchemasConfiguration();
            $defaultSchema = config('graphql.default_schema', 'default');

            foreach ($schemas as $schemaName => $schemaConfig) {
                // A schemaConfig can in fact be a \GraphQL\Type\Schema object
                // in which case no further information can be extracted from it
                if (is_object($schemaConfig)) {
                    $schemaConfig = [];
                }

                $actions = array_filter([
                    'uses' => $schemaConfig['controller'] ?? $routeConfig['controller'] ?? GraphQLController::class . '@query',
                    'middleware' => $schemaConfig['middleware'] ?? $routeConfig['middleware'] ?? null,
                ]);

                // Add route for each schema…
                $router->addRoute(
                    ['GET', 'POST'],
                    $schemaName,
                    $actions + ['as' => "graphql.$schemaName"]
                );

                // … and the default schema against the group itself
                if ($schemaName === $defaultSchema) {
                    $router->addRoute(
                        ['GET', 'POST'],
                        '',
                        $actions + ['as' => 'graphql']
                    );
                }
            }
        }
    );
}

if (config('graphql.graphiql.display', true)) {
    /** @var Router $router */
    $router = app('router');
    $graphiqlConfig = config('graphql.graphiql');

    $router->group(
        [
            'prefix' => $graphiqlConfig['prefix'] ?? 'graphiql',
            'middleware' => $graphiqlConfig['middleware'] ?? [],
        ],
        function (Router $router) use ($graphiqlConfig): void {
            $actions = [
                'uses' => $graphiqlConfig['controller'] ?? GraphQLController::class . '@graphiql',
            ];

            // A graphiql route for each schema…
            /** @var string $schemaName */
            foreach (array_keys(config('graphql.schemas', [])) as $schemaName) {
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
