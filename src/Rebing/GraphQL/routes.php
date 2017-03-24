<?php

Route::group([
    'prefix'        => config('graphql.prefix'),
    'middleware'    => config('graphql.middleware', [])
], function($router)
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
            Route::post(preg_replace($schemaParameterPattern, '', $queryRoute), [
                'uses'          => $queryController,
                'middleware'    => $defaultMiddleware,
            ]);
            
            foreach(config('graphql.schemas') as $name => $schema)
            {
                Route::post(preg_replace($schemaParameterPattern, $name, $queryRoute), [
                    'uses'          => $queryController,
                    'middleware'    => array_get($schema, 'middleware', []),
                ]);
            }
        }
        else
        {
            Route::get($queryRoute, [
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
            Route::post(preg_replace($schemaParameterPattern, '', $mutationRoute), [
                'uses'          => $mutationController,
                'middleware'    => $defaultMiddleware,
            ]);

            foreach(config('graphql.schemas') as $name => $schema)
            {
                Route::post(preg_replace($schemaParameterPattern, $name, $queryRoute), [
                    'uses'          => $mutationController,
                    'middleware'    => array_get($schema, 'middleware', []),
                ]);
            }
        }
        else
        {
            Route::post($mutationRoute, [
                'uses'  => $mutationController
            ]);
        }
    }
});
