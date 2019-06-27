<?php

declare(strict_types=1);

use Illuminate\Support\Arr;
use Rebing\GraphQL\Helpers;
use Rebing\GraphQL\GraphQLController;

$router = app('router');

$router->group(array_merge([
    'prefix'        => config('graphql.prefix'),
    'middleware'    => config('graphql.middleware', []),
], config('graphql.route_group_attributes', [])), function ($router): void {
    /** @var \Illuminate\Routing\Router|\Laravel\Lumen\Routing\Router $router */
    // Routes
    $routes = config('graphql.routes');
    $queryRoute = null;
    $mutationRoute = null;
    if (is_array($routes)) {
        $queryRoute = Arr::get($routes, 'query');
        $mutationRoute = Arr::get($routes, 'mutation');
    } else {
        $queryRoute = $routes;
        $mutationRoute = $routes;
    }

    // Controllers
    $controllers = config('graphql.controllers', GraphQLController::class.'@query');
    $queryController = null;
    $mutationController = null;
    if (is_array($controllers)) {
        $queryController = Arr::get($controllers, 'query');
        $mutationController = Arr::get($controllers, 'mutation');
    } else {
        $queryController = $controllers;
        $mutationController = $controllers;
    }

    $schemaParameterPattern = '/\{\s*graphql\_schema\s*\?\s*\}/';

    // Query
    if ($queryRoute) {
        if (preg_match($schemaParameterPattern, $queryRoute)) {
            $defaultMiddleware = config('graphql.schemas.'.config('graphql.default_schema').'.middleware', []);
            $defaultMethod = config('graphql.schemas.'.config('graphql.default_schema').'.method', ['get', 'post']);

            foreach ($defaultMethod as $method) {
                $routeName = 'graphql.query';
                if ($method !== 'get') {
                    $routeName .= ".$method";
                }
                $router->{$method}(
                    preg_replace($schemaParameterPattern, '', $queryRoute),
                    [
                        'uses'          => $queryController,
                        'middleware'    => $defaultMiddleware,
                        'as'            => $routeName,
                    ]
                );
            }

            foreach (config('graphql.schemas') as $name => $schema) {
                foreach (Arr::get($schema, 'method', ['get', 'post']) as $method) {
                    $routeName = "graphql.$name";
                    if ($method !== 'get') {
                        $routeName .= ".$method";
                    }
                    $route = $router->{$method}(
                        Rebing\GraphQL\GraphQL::routeNameTransformer($name, $schemaParameterPattern, $queryRoute),
                        [
                            'uses'          => $queryController,
                            'middleware'    => Arr::get($schema, 'middleware', []),
                            'as'            => $routeName,
                        ]
                    );

                    if (! Helpers::isLumen()) {
                        $route->where($name, $name);
                    }
                }
            }
        } else {
            $router->get($queryRoute, [
                'uses' => $queryController,
                'as'   => 'graphql.query',
            ]);
            $router->post($queryRoute, [
                'uses' => $queryController,
                'as'   => 'graphql.query.post',
            ]);
        }
    }

    // Mutation routes (define only if different than query)
    if ($mutationRoute && $mutationRoute !== $queryRoute) {
        if (preg_match($schemaParameterPattern, $mutationRoute)) {
            $defaultMiddleware = config('graphql.schemas.'.config('graphql.default_schema').'.middleware', []);
            $defaultMethod = config('graphql.schemas.'.config('graphql.default_schema').'.method', ['get', 'post']);

            foreach ($defaultMethod as $method) {
                $routeName = 'graphql.mutation';
                if ($method !== 'get') {
                    $routeName .= ".$method";
                }
                $router->{$method}(
                    preg_replace($schemaParameterPattern, '', $mutationRoute),
                    [
                        'uses'          => $mutationController,
                        'middleware'    => $defaultMiddleware,
                        'as'            => $routeName,
                    ]
                );
            }

            foreach (config('graphql.schemas') as $name => $schema) {
                foreach (Arr::get($schema, 'method', ['get', 'post']) as $method) {
                    $routeName = "graphql.mutation.$name";
                    if ($method !== 'get') {
                        $routeName .= ".$method";
                    }
                    $route = $router->{$method}(
                        Rebing\GraphQL\GraphQL::routeNameTransformer($name, $schemaParameterPattern, $mutationRoute),
                        [
                            'uses'          => $mutationController,
                            'middleware'    => Arr::get($schema, 'middleware', []),
                            'as'            => $routeName,
                        ]
                    );

                    if (! Helpers::isLumen()) {
                        $route->where($name, $name);
                    }
                }
            }
        } else {
            $router->get($mutationRoute, [
                'uses' => $mutationController,
                'as'   => 'graphql.mutation',
            ]);
            $router->post($mutationRoute, [
                'uses' => $mutationController,
                'as'   => 'graphql.mutation.post',
            ]);
        }
    }
});

if (config('graphql.graphiql.display', true)) {
    $router->group([
        'prefix'        => config('graphql.graphiql.prefix', 'graphiql'),
        'middleware'    => config('graphql.graphiql.middleware', []),
    ], function ($router): void {
        /** @var \Illuminate\Routing\Router|\Laravel\Lumen\Routing\Router $router */
        $graphiqlController = config('graphql.graphiql.controller', GraphQLController::class.'@graphiql');
        $schemaParameterPattern = '/\{\s*graphql\_schema\s*\?\s*\}/';
        $graphiqlAction = ['uses' => $graphiqlController];

        foreach (config('graphql.schemas') as $name => $schema) {
            $route = $router->get(
                Rebing\GraphQL\GraphQL::routeNameTransformer($name, $schemaParameterPattern, '{graphql_schema?}'),
                $graphiqlAction + ['as' => "graphql.graphiql.$name"]
            );
            if (! Helpers::isLumen()) {
                $route->where($name, $name);
            }

            $route = $router->post(
                Rebing\GraphQL\GraphQL::routeNameTransformer($name, $schemaParameterPattern, '{graphql_schema?}'),
                $graphiqlAction + ['as' => "graphql.graphiql.$name.post"]
            );
            if (! Helpers::isLumen()) {
                $route->where($name, $name);
            }
        }

        $router->get('/', $graphiqlAction + ['as' => 'graphql.graphiql']);
        $router->post('/', $graphiqlAction + ['as' => 'graphql.graphiql.post']);
    });
}
