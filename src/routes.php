<?php

declare(strict_types = 1);

use Illuminate\Support\Arr;
use Rebing\GraphQL\GraphQLController;

$router = app('router');
$schemaParameterPattern = '/\{\s*graphql\_schema\s*\?\s*\}/';

$router->group(array_merge([
    'prefix' => config('graphql.prefix'),
    'middleware' => config('graphql.middleware', []),
], config('graphql.route_group_attributes', [])), function ($router) use ($schemaParameterPattern): void {
    /** @var \Illuminate\Routing\Router $router */

    // Routes and controllers
    $routes = config('graphql.routes');
    $controllers = config('graphql.controllers', GraphQLController::class . '@query');

    $queryTypesMap = [
        'query' => [],
        'mutation' => [],
    ];

    /** @var array $queryTypesMap */
    $queryTypesMap = array_combine(
        array_keys($queryTypesMap),
        array_map(function (string $type) use ($routes, $controllers, $queryTypesMap) {
            $row = $queryTypesMap[$type];
            $row['route'] = is_array($routes) ? Arr::get($routes, $type) : $routes;
            $row['controller'] = is_array($controllers) ? Arr::get($controllers, $type) : $controllers;

            return $row;
        }, array_keys($queryTypesMap))
    );

    // Specific query type routes
    $queryTypesMapWithRoutes = array_filter($queryTypesMap, function (array $row, string $type) use ($queryTypesMap) {
        if ('mutation' === $type && $queryTypesMap['mutation']['route'] === $queryTypesMap['query']['route']) {
            return;
        }

        return null !== $row['route'];
    }, ARRAY_FILTER_USE_BOTH);

    if ($queryTypesMapWithRoutes) {
        $schemaConfig = Rebing\GraphQL\GraphQL::getNormalizedSchemasConfiguration();

        $defaultConfig = Arr::get($schemaConfig, config('graphql.default_schema'), []);
        $defaultMiddleware = $defaultConfig['middleware'] ?? [];
        $defaultMethod = $defaultConfig['method'] ?? ['get', 'post'];

        foreach ($queryTypesMapWithRoutes as $type => $info) {
            if (preg_match($schemaParameterPattern, $info['route'])) {
                foreach ($defaultMethod as $method) {
                    $routeName = "graphql.{$type}";

                    if ('get' !== $method) {
                        $routeName .= ".$method";
                    }
                    $router->{$method}(
                        preg_replace($schemaParameterPattern, '', $info['route']),
                        [
                            'uses' => $info['controller'],
                            'middleware' => $defaultMiddleware,
                            'as' => $routeName,
                        ]
                    );
                }

                foreach ($schemaConfig as $name => $schema) {
                    foreach (Arr::get($schema, 'method', ['get', 'post']) as $method) {
                        $routeName = "graphql.$name";

                        if ('get' !== $method) {
                            $routeName .= ".$method";
                        }
                        $route = $router->{$method}(
                            Rebing\GraphQL\GraphQL::routeNameTransformer($name, $schemaParameterPattern, $info['route']),
                            [
                                'uses' => $info['controller'],
                                'middleware' => Arr::get($schema, 'middleware', []),
                                'as' => $routeName,
                            ]
                        );

                        $route->where($name, $name);
                    }
                }
            } else {
                $router->get($info['route'], [
                    'uses' => $info['controller'],
                    'as' => "graphql.{$type}",
                ]);
                $router->post($info['route'], [
                    'uses' => $info['controller'],
                    'as' => "graphql.{$type}.post",
                ]);
            }
        }
    }
});

if (config('graphql.graphiql.display', true)) {
    $router->group([
        'prefix' => config('graphql.graphiql.prefix', 'graphiql'),
        'middleware' => config('graphql.graphiql.middleware', []),
    ], function ($router) use ($schemaParameterPattern): void {
        /** @var \Illuminate\Routing\Router $router */
        $graphiqlController = config('graphql.graphiql.controller', GraphQLController::class . '@graphiql');

        $graphiqlAction = ['uses' => $graphiqlController];

        foreach (config('graphql.schemas') as $name => $schema) {
            $route = $router->get(
                Rebing\GraphQL\GraphQL::routeNameTransformer($name, $schemaParameterPattern, '{graphql_schema?}'),
                $graphiqlAction + ['as' => "graphql.graphiql.$name"]
            );
            $route->where($name, $name);

            $route = $router->post(
                Rebing\GraphQL\GraphQL::routeNameTransformer($name, $schemaParameterPattern, '{graphql_schema?}'),
                $graphiqlAction + ['as' => "graphql.graphiql.$name.post"]
            );
            $route->where($name, $name);
        }

        $router->get('/', $graphiqlAction + ['as' => 'graphql.graphiql']);
        $router->post('/', $graphiqlAction + ['as' => 'graphql.graphiql.post']);
    });
}
