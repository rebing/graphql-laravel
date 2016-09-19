<?php


use example\Mutation\ExampleMutation;
use example\Query\ExampleQuery;
use example\Type\ExampleRelationType;
use example\Type\ExampleType;

return [
    
    // The prefix for routes
    'prefix' => 'graphql',
    
    // The routes to make GraphQL request. Either a string that will apply
    // to both query and mutation or an array containing the key 'query' and/or
    // 'mutation' with the according Route
    //
    // Example:
    //
    // 'routes' => [
    //     'query' => '/query',
    //     'mutation' => '/mutation'
    // ]
    //
    'routes' => '/',
    
    // The controller to use in GraphQL request. Either a string that will apply
    // to both query and mutation or an array containing the key 'query' and/or
    // 'mutation' with the according Controller and method
    //
    // Example:
    //
    // 'controllers' => [
    //     'query' => '\Rebing\GraphQL\GraphQLController@query',
    //     'mutation' => '\Rebing\GraphQL\GraphQLController@mutation'
    // ]
    //
    'controllers' => '\Rebing\GraphQL\GraphQLController@query',

    // Any middleware for the graphql route group
    'middleware' => [],
    
    // The schema for query and/or mutation. It expects an array to provide
    // both the 'query' fields and the 'mutation' fields. You can also
    // provide directly an object GraphQL\Schema
    //
    // Example:
    //
    // 'schema' => new Schema($queryType, $mutationType)
    //
    // or
    //
    // 'schema' => [
    //     'query' => [
    //          'users' => 'App\GraphQL\Query\UsersQuery'
    //      ],
    //     'mutation' => [
    //          
    //     ]
    // ]
    //
    'schema' => [
        'query' => [
            'example_query' => ExampleQuery::class,
        ],
        'mutation' => [
            'example_mutation'  => ExampleMutation::class,
        ],
    ],
    
    // The types available in the application. You can then access it from the
    // facade like this: GraphQL::type('user')
    //
    // Example:
    //
    // 'types' => [
    //     'user' => 'App\GraphQL\Type\UserType'
    // ]
    //
    'types' => [
        'example'           => ExampleType::class,
        'relation_example'  => ExampleRelationType::class,
    ],
    
    // This callable will be passed the Error object for each errors GraphQL catch.
    // The method should return an array representing the error.
    // Typically:
    // [
    //     'message' => '',
    //     'locations' => []
    // ]
    'error_formatter' => ['\Rebing\GraphQL\GraphQL', 'formatError']
    
];
