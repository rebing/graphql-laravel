<?php

Route::group(array_merge([
    'prefix'        => config('graphql.prefix'),
    'middleware'    => config('graphql.middleware', []),
], config('graphql.route_group_attributes', [])), function($router)
{
    // Routes
    $routes = config('graphql.routes');
    $queryRoute = null;
    $mutationRoute = null;
    if(is_array($routes))
    {
        $queryRoute = array_get($routes, 'query');
        $mutationRoute = array_get($routes, 'mutation');
    }
    else
    {
        $queryRoute = $routes;
        $mutationRoute = $routes;
    }

    // Controllers
    $controllers = config('graphql.controllers', \Rebing\GraphQL\GraphQLController::class . '@query');
    $queryController = null;
    $mutationController = null;
    if(is_array($controllers))
    {
        $queryController = array_get($controllers, 'query');
        $mutationController = array_get($controllers, 'mutation');
    }
    else
    {
        $queryController = $controllers;
        $mutationController = $controllers;
    }

    $schemaParameterPattern = '/\{\s*graphql\_schema\s*\?\s*\}/';

    // Query
    if($queryRoute)
    {
        if(preg_match($schemaParameterPattern, $queryRoute))
        {
            $defaultMiddleware = config('graphql.schemas.' . config('graphql.default_schema') . '.middleware', []);
            $defaultMethod = config('graphql.schemas.' . config('graphql.default_schema') . '.method', ['get', 'post']);
            Route::match($defaultMethod, preg_replace($schemaParameterPattern, '', $queryRoute), [
                'uses'          => $queryController,
                'middleware'    => $defaultMiddleware,
            ]);

            foreach(config('graphql.schemas') as $name => $schema)
            {
                Route::match(
                    array_get($schema, 'method', ['get', 'post']),
                    Rebing\GraphQL\GraphQL::routeNameTransformer($name, $schemaParameterPattern, $queryRoute),
                    [
                        'uses'          => $queryController,
                        'middleware'    => array_get($schema, 'middleware', []),
                    ]
                )->where($name, $name);
            }
        }
        else
        {
            Route::match(['get', 'post'], $queryRoute, [
                'uses'  => $queryController
            ]);
        }
    }

    // Mutation
    if($mutationRoute)
    {
        if(preg_match($schemaParameterPattern, $mutationRoute))
        {
            $defaultMiddleware = config('graphql.schemas.' . config('graphql.default_schema') . '.middleware', []);
            $defaultMethod = config('graphql.schemas.' . config('graphql.default_schema') . '.method', ['get', 'post']);
            Route::match(
                $defaultMethod,
                preg_replace($schemaParameterPattern, '', $mutationRoute),
                [
                    'uses'          => $mutationController,
                    'middleware'    => $defaultMiddleware,
                ]
            );

            foreach(config('graphql.schemas') as $name => $schema)
            {
                Route::match(
                    array_get($schema, 'method', ['get', 'post']),
                    Rebing\GraphQL\GraphQL::routeNameTransformer($name, $schemaParameterPattern, $queryRoute),
                    [
                        'uses'          => $mutationController,
                        'middleware'    => array_get($schema, 'middleware', []),
                    ]
                )->where($name, $name);
            }
        }
        else
        {
            Route::match(['get', 'post'], $mutationRoute, [
                'uses'  => $mutationController
            ]);
        }
    }
});

if (config('graphql.graphiql.display', true))
{
    Route::group([
        'prefix'        => config('graphql.graphiql.prefix', 'graphiql'),
        'middleware'    => config('graphql.graphiql.middleware', [])
    ], function ($router)
    {
        $graphiqlController =  config('graphql.graphiql.controller') ?? \Rebing\GraphQL\GraphQLController::class . '@graphiql';
        $schemaParameterPattern = '/\{\s*graphql\_schema\s*\?\s*\}/';
        foreach (config('graphql.schemas') as $name => $schema)
        {
            Route::match(
                ['get', 'post'],
                Rebing\GraphQL\GraphQL::routeNameTransformer($name, $schemaParameterPattern, '{graphql_schema?}'),
                ['uses' => $graphiqlController]
            )->where($name, $name);
        }

        Route::match(
            ['get', 'post'],
            '/',
            ['uses'  => $graphiqlController]
        );
    });
}
