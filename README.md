# Laravel GraphQL

[![Latest Stable Version](https://poser.pugx.org/rebing/graphql-laravel/v/stable)](https://packagist.org/packages/rebing/graphql-laravel)
[![License](https://poser.pugx.org/rebing/graphql-laravel/license)](https://packagist.org/packages/rebing/graphql-laravel)
[![Tests](https://github.com/rebing/graphql-laravel/workflows/Tests/badge.svg)](https://github.com/rebing/graphql-laravel/actions?query=workflow%3ATests)
[![Downloads](https://img.shields.io/packagist/dt/rebing/graphql-laravel.svg?style=flat-square)](https://packagist.org/packages/rebing/graphql-laravel)
[![Get on Slack](https://img.shields.io/badge/slack-join-orange.svg)](https://rebing-graphql.slack.com/join/shared_invite/enQtNTE5NjQzNDI5MzQ4LTdhNjk0ZGY1N2U1YjE4MGVlYmM2YTc2YjQ0MmIwODY5MWMwZWIwYmY1MWY4NTZjY2Q5MzdmM2Q3NTEyNDYzZjc#/shared-invite/email)

This package provides a code-first integration of GraphQL for Laravel. It is based on the [PHP port of GraphQL reference implementation](https://github.com/webonyx/graphql-php). You define your schema entirely in PHP classes (types, queries, mutations) rather than in `.graphql` schema files. You can find more information about GraphQL in the [Introduction to GraphQL](https://graphql.org/learn/) or you can read the [GraphQL specifications](https://spec.graphql.org/).

* Allows creating **queries** and **mutations** as request endpoints
* Supports multiple schemas
  * per schema queries/mutations/types 
  * per schema HTTP middlewares
  * per schema GraphQL execution middlewares
* Custom GraphQL **resolver middleware** can be defined for each query/mutation
  
When using the `SelectFields` class for Eloquent support, additional features are available:
* Queries return **types**, which can have custom **privacy** settings.
* The queried fields will have the option to be retrieved **dynamically** from the database.

> **Note:** GraphQL **subscriptions** are not supported by this package. If you
> need real-time push functionality, consider a dedicated solution like
> [Lighthouse](https://lighthouse-php.com/) (which has subscription support) or
> implement subscriptions separately via Laravel broadcasting / WebSockets.

## Requirements

| Dependency | Version |
|-----------|---------|
| PHP | ^8.2 |
| Laravel | 12.x - 13.x |
| webonyx/graphql-php | ^15.22.1 |

Optional dependencies:

| Package | Purpose |
|---------|---------|
| `open-telemetry/api` ^1.0 | Required for the [OpenTelemetry tracing driver](#tracing--observability) |
| `mll-lab/laravel-graphiql` | Interactive in-browser [GraphiQL](https://github.com/mll-lab/laravel-graphiql) IDE |

## Installation

Require the package via Composer:
```bash
composer require rebing/graphql-laravel
```

Publish the configuration file via Laravel artisan:
```bash
php artisan vendor:publish --provider="Rebing\GraphQL\GraphQLServiceProvider"
```

Review the configuration file:
```
config/graphql.php
```

## Quick Start

Get a working GraphQL endpoint in under 5 minutes -- no database required.

### 1. Create a Type

Use the artisan generator to scaffold a type:

```bash
php artisan make:graphql:type BookType
```

Edit the generated `app/GraphQL/Types/BookType.php`:

```php
declare(strict_types = 1);
namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class BookType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Book',
        'description' => 'A book',
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'The id of the book',
            ],
            'title' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The title of the book',
            ],
            'author' => [
                'type' => Type::string(),
                'description' => 'The name of the author',
            ],
        ];
    }
}
```

### 2. Create a Query

```bash
php artisan make:graphql:query BooksQuery
```

Edit `app/GraphQL/Queries/BooksQuery.php`:

```php
declare(strict_types = 1);
namespace App\GraphQL\Queries;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class BooksQuery extends Query
{
    protected $attributes = [
        'name' => 'books',
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::listOf(Type::nonNull(GraphQL::type('Book'))));
    }

    public function args(): array
    {
        return [
            'title' => [
                'type' => Type::string(),
                'description' => 'Filter by title',
            ],
        ];
    }

    public function resolve($root, array $args): array
    {
        $books = [
            ['id' => 1, 'title' => 'The Great Gatsby', 'author' => 'F. Scott Fitzgerald'],
            ['id' => 2, 'title' => '1984', 'author' => 'George Orwell'],
            ['id' => 3, 'title' => 'To Kill a Mockingbird', 'author' => 'Harper Lee'],
        ];

        if (isset($args['title'])) {
            return array_values(array_filter($books, fn ($book) => str_contains($book['title'], $args['title'])));
        }

        return $books;
    }
}
```

### 3. Register in config

Add the type and query to the `default` schema in `config/graphql.php`:

```php
'schemas' => [
    'default' => [
        'query' => [
            App\GraphQL\Queries\BooksQuery::class,
        ],
        'mutation' => [],
        'types' => [
            App\GraphQL\Types\BookType::class,
        ],
    ],
],
```

### 4. Test it

Start the dev server and send a query:

```bash
php artisan serve
```

```bash
curl -X POST -H "Content-Type: application/json" \
  -d '{"query": "{ books { id title author } }"}' \
  http://localhost:8000/graphql
```

Expected response:

```json
{
    "data": {
        "books": [
            {"id": 1, "title": "The Great Gatsby", "author": "F. Scott Fitzgerald"},
            {"id": 2, "title": "1984", "author": "George Orwell"},
            {"id": 3, "title": "To Kill a Mockingbird", "author": "Harper Lee"}
        ]
    }
}
```

Try filtering with an argument:

```bash
curl -X POST -H "Content-Type: application/json" \
  -d '{"query": "{ books(title: \"1984\") { id title } }"}' \
  http://localhost:8000/graphql
```

> **Tip:** For an interactive experience, install [GraphiQL](https://github.com/mll-lab/laravel-graphiql)
> (`composer require mll-lab/laravel-graphiql --dev`) and visit `/graphiql` in your browser.

> **Note:** Introspection is disabled by default. To enable it during development
> (required for GraphiQL and IDE tooling), set `GRAPHQL_DISABLE_INTROSPECTION=false`
> in your `.env` file.

### What's next?

You now have a working GraphQL API. From here you can:

- **Use Eloquent models** -- see [Creating a query](#creating-a-query) for a full example with database-backed types and the `SelectFields` helper for optimized eager loading
- **Add mutations** -- see [Creating a mutation](#creating-a-mutation) to modify data
- **Add validation** -- see [Validation](#validation) for built-in Laravel validation rules on arguments
- **Add authorization** -- see [Authorization](#authorization) for per-operation access control
- **Explore all generators** -- run `php artisan list make:graphql` to see all 12 available scaffolding commands

## Usage

- [Laravel GraphQL](#laravel-graphql)
  - [Installation](#installation)
  - [Quick Start](#quick-start)
  - [Usage](#usage)
    - [Concepts](#concepts)
      - [A word on declaring a field `nonNull`](#a-word-on-declaring-a-field-nonnull)
    - [Data loading](#data-loading)
    - [Middleware Overview](#middleware-overview)
      - [HTTP middleware](#http-middleware)
      - [GraphQL execution middleware](#graphql-execution-middleware)
      - [GraphQL resolver middleware](#graphql-resolver-middleware)
    - [Schemas](#schemas)
      - [Route attributes](#route-attributes)
      - [Schema classes](#schema-classes)
    - [Creating a query](#creating-a-query)
    - [Creating a mutation](#creating-a-mutation)
      - [File uploads](#file-uploads)
        - [Vue.js example](#vuejs-example)
        - [Vanilla JavaScript](#vanilla-javascript)
    - [Validation](#validation)
      - [Example defining rules in each argument](#example-defining-rules-in-each-argument)
      - [Example using the `rules()` method](#example-using-the-rules-method)
      - [Example using Laravel's validator directly](#example-using-laravels-validator-directly)
      - [Handling validation errors](#handling-validation-errors)
      - [Customizing error messages](#customizing-error-messages)
      - [Customizing attributes](#customizing-attributes)
      - [Cross-field validation rules in nested input types](#cross-field-validation-rules-in-nested-input-types)
      - [Misc notes](#misc-notes)
    - [Resolve method](#resolve-method)
    - [Resolver middleware](#resolver-middleware)
      - [Defining middleware](#defining-middleware)
      - [Registering middleware](#registering-middleware)
      - [Terminable middleware](#terminable-middleware)
    - [Authorization](#authorization)
    - [Privacy](#privacy)
    - [Query variables](#query-variables)
    - [Custom field](#custom-field)
      - [Even better reusable fields](#even-better-reusable-fields)
    - [Eager loading relationships](#eager-loading-relationships)
    - [Type relationship query](#type-relationship-query)
    - [Pagination](#pagination)
    - [Batching](#batching)
    - [Scalar types](#scalar-types)
    - [Enums](#enums)
    - [SelectFields and abstract types](#selectfields-and-abstract-types)
    - [Unions](#unions)
    - [Interfaces](#interfaces)
      - [Supporting custom queries on interface relations](#supporting-custom-queries-on-interface-relations)
      - [Sharing interface fields](#sharing-interface-fields)
    - [Input Object](#input-object)
    - [OneOf Input Objects](#oneof-input-objects)
      - [Creating a OneOf Input Type](#creating-a-oneof-input-type)
      - [Using OneOf Input Types](#using-oneof-input-types)
      - [Generating OneOf Input Types](#generating-oneof-input-types)
    - [Type modifiers](#type-modifiers)
    - [Field and input alias](#field-and-input-alias)
    - [JSON columns](#json-columns)
    - [Field deprecation](#field-deprecation)
    - [Default field resolver](#default-field-resolver)
    - [Macros](#macros)
    - [Automatic Persisted Queries support](#automatic-persisted-queries-support)
      - [Notes](#notes)
      - [Client example](#client-example)
    - [Tracing / Observability](#tracing--observability)
      - [Enabling OpenTelemetry](#enabling-opentelemetry)
      - [Per-field resolver tracing](#per-field-resolver-tracing)
      - [Custom tracing drivers](#custom-tracing-drivers)
      - [Per-schema tracing](#per-schema-tracing)
  - [Security](#security)
    - [Introspection](#introspection)
    - [Query depth limiting](#query-depth-limiting)
    - [Query complexity analysis](#query-complexity-analysis)
    - [Batching limits](#batching-limits)
    - [Recommended production configuration](#recommended-production-configuration)
  - [Error handling](#error-handling)
    - [Built-in error types](#built-in-error-types)
    - [Error response format](#error-response-format)
    - [Error reporting](#error-reporting)
    - [Customizing error formatting](#customizing-error-formatting)
  - [Misc features](#misc-features)
    - [Detecting unused variables](#detecting-unused-variables)
  - [Configuration options](#configuration-options)
  - [Performance considerations](#performance-considerations)
    - [Wrap Types](#wrap-types)
      - [Using wrap types with `SelectFields`](#using-wrap-types-with-selectfields)
  - [Known Limitations](#known-limitations)
    - [SelectFields related](#selectfields-related)
  - [GraphQL testing clients](#graphql-testing-clients)
  - [Testing](#testing)
  - [Upgrading](#upgrading)

### Concepts

Before diving head first into code, it's good to familiarize yourself with the
concepts surrounding GraphQL. If you've already experience with GraphQL, feel
free to skip this part.

- "schema"  
  A GraphQL schema defines all the queries, mutations and types
  associated with it.
- "queries" and "mutations"  
  The "methods" you call in your GraphQL request (think about your REST endpoint)
- "types"  
  Besides the primitive scalars like int and string, custom "shapes" can be
  defined and returned via custom types. They can map to your database models or
  basically any data you want to return.
- "resolver"  
  Any time data is returned, it is "resolved". Usually in query/mutations this
  specified the primary way to retrieve your data (e.g. using `SelectFields` or
  [dataloaders](https://github.com/overblog/dataloader-php))

Typically, all queries/mutations/types are defined using the `$attributes`
property and the `args()` / `fields()` methods as well as the `resolve()` method.

args/fields again return a configuration array for each field they supported.
Those fields usually support these shapes
- the "key" is the name of the field
- `type` (required): a GraphQL specifier for the type supported here
  
Optional keys are:
- `description`: made available when introspecting the GraphQL schema
- `resolve`: override the default field resolver
- `deprecationReason`: document why something is deprecated

#### A word on declaring a field `nonNull`

It's quite common, and actually good practice, to see the gracious use of
`Type::nonNull()` on any kind of input and/or output fields.

**The more specific the intent of your type system, the better for the consumer.**

Some examples

- if you require a certain field for a query/mutation argument, declare it non
  null
- if you know that your (e.g. model) field can never return null (e.g. users ID,
  email, etc.), declare it no null
- if you return a list of something, like e.g. tags, which is a) always an array
  (even empty) and b) shall not contain `null` values, declare the type like this:\
  `Type::nonNull(Type::listOf(Type::nonNull(Type::string())))`

There exists a lot of tooling in the GraphQL ecosystem, which benefits the more
specific your type system is.

### Data loading

The act of loading/retrieving your data is called "resolving" in GraphQL. GraphQL
itself does **not** define the "how" and leaves it up to the implementor.

In the context of Laravel it's natural to assume the primary source of data will
be Eloquent. This library therefore provides a convenient helper called
`SelectFields` which tries its best to
[eager load relations](#eager-loading-relationships) and to
[avoid n+1 problems](https://laravel.com/docs/eloquent-relationships#eager-loading).

Be aware that this is not the only way and it's also common to use _concepts_
called "dataloaders". They usually take advantage of "deferred" executions of
resolved fields, as explained in [graphql-php solving n+1 problem](https://webonyx.github.io/graphql-php/data-fetching/#solving-n1-problem).

The gist is that you can use any kind of data source you like (Eloquent,
static data, ElasticSearch results, caching, etc.) in your resolvers but you've
to be mindful of the execution model to avoid repetitive fetches and perform
smart pre-fetching of your data.

### Middleware Overview

The following middleware concepts are supported:

- HTTP middleware (i.e. from Laravel)
- GraphQL execution middleware
- GraphQL resolver middleware

Briefly said, a middleware _usually_ is a class:
- with a `handle` method
- receiving a fixed set of parameters plus a callable for the next middleware
- is responsible for calling the "next" middleware\
  Usually a middleware does just that but may decide to not do that and
  just return
- has the freedom to mutate the parameters passed on

#### HTTP middleware

Any [Laravel compatible HTTP middleware](https://laravel.com/docs/middleware)
can be provided on a global level for all GraphQL endpoints via the config
`graphql.route.middleware` or on a per-schema basis via
`graphql.schemas.<yourschema>.middleware`. The per-schema middleware overrides
the global one.

#### GraphQL execution middleware

The processing of a GraphQL request, henceforth called "execution", flows
through a set of middlewares.

They can be set on global level via `graphql.execution_middleware` or per-schema
via `graphql.schemas.<yourschema>.execution_middleware`.

By default, the recommended set of middlewares is provided on the global level.

Note: the execution of the GraphQL request _itself_ is also implemented via a
middleware, which is usually expected to be called last (and does not call
further middlewares). In case you're interested in the details, please see
`\Rebing\GraphQL\GraphQL::appendGraphqlExecutionMiddleware`

#### GraphQL resolver middleware

After the HTTP middleware and the execution middleware is applied, the
"resolver middleware" is executed for the query/mutation being targeted
**before** the actual `resolve()` method is called.

See [Resolver middleware](#resolver-middleware) for more details.

### Schemas

Schemas are required for defining GraphQL endpoints. You can define multiple
schemas and assign different **HTTP middleware** and **execution middleware** to
them, in addition to the global middleware. For example:

```php
'default_schema' => 'default',

'schemas' => [
    'default' => [
        'query' => [
            ExampleQuery::class,
        ],
        'mutation' => [
            ExampleMutation::class,
        ],
        'types' => [
        
        ],
    ],
    'user' => [
        'query' => [
            App\GraphQL\Queries\ProfileQuery::class
        ],
        'mutation' => [

        ],
        'types' => [
        
        ],
        'middleware' => ['auth'],
        // Which HTTP methods to support; must be given in UPPERCASE!
        // Default is POST only; enable GET explicitly if needed
        'method' => ['GET', 'POST'], 
        'execution_middleware' => [
            \Rebing\GraphQL\Support\ExecutionMiddleware\UnusedVariablesMiddleware::class,
        ],
        // Route attributes applied to the generated HTTP route for this schema
        // Example: expose this schema on a dedicated subdomain
        'route_attributes' => [
            'domain' => 'api.example.com',
        ],
        // Override the default controller for this schema.
        // Supports string ('Class@method') and array ([Class::class, 'method']) formats.
        // The controller method receives the same parameters as GraphQLController@query.
        // 'controller' => App\Http\Controllers\MyGraphQLController::class . '@query',
    ],
],
```

Together with the configuration, in a way the schema defines also the route by
which it is accessible. Per the default configuration of `prefix = graphql`, the
_default_ schema is accessible via `/graphql`.


#### Route attributes

You can customize the HTTP route generated for a specific schema using the `route_attributes` key.
This is useful for setting parameters supported by Laravel routes, e.g. a custom `domain`.
The attributes are merged into the route's action array, so standard Laravel route attributes
like `domain`, `prefix`, `as` (route name), and `where` (parameter constraints) are all supported.

```php
'schemas' => [
    'with_custom_domain' => [
        'query' => [
            App\GraphQL\Queries\UsersQuery::class,
        ],
        'middleware' => ['auth:api'],
        'route_attributes' => [
            'domain' => 'api.example.com',
        ],
    ],
]
```


#### Schema classes

You may alternatively define the configuration of a schema in a class that implements `ConfigConvertible`.

In your config, you can reference the name of the class, rather than an array.

```php
'schemas' => [
    'default' => DefaultSchema::class
]
```

```php
declare(strict_types = 1);
namespace App\GraphQL\Schemas;

use Rebing\GraphQL\Support\Contracts\ConfigConvertible;

class DefaultSchema implements ConfigConvertible
{
    public function toConfig(): array
    {
        return [
            'query' => [
                ExampleQuery::class,
            ],
            'mutation' => [
                ExampleMutation::class,
            ],
            'types' => [
            
            ],
        ];
    }
}
```

You can use the `php artisan make:graphql:schemaConfig` command to create a new schema configuration class automatically.

### Creating a query

First you usually create a type you want to return from the query. The Eloquent `'model'` is only required if specifying relations.

> **Note:** The `selectable` key defaults to `true`, meaning `SelectFields` will include the
> field in the SQL `SELECT`. Set it to `false` for computed/virtual fields that don't correspond
> to a database column (e.g. accessors, custom resolvers).

```php
declare(strict_types = 1);
namespace App\GraphQL\Types;

use App\Models\User;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class UserType extends GraphQLType
{
    protected $attributes = [
        'name'          => 'User',
        'description'   => 'A user',
        // Note: only necessary if you use `SelectFields`
        'model'         => User::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The id of the user',
                // Use 'alias', if the database column is different from the type name.
                // This is supported for discrete values as well as relations.
                // - you can also use `DB::raw()` to solve more complex issues
                // - or a callback returning the value (string or `DB::raw()` result)
                'alias' => 'user_id',
            ],
            'email' => [
                'type' => Type::string(),
                'description' => 'The email of user',
                'resolve' => function($root, array $args) {
                    // If you want to resolve the field yourself,
                    // it can be done here
                    return strtolower($root->email);
                }
            ],
            // Uses the 'getIsMeAttribute' function on our custom User model
            'isMe' => [
                'type' => Type::boolean(),
                'description' => 'True, if the queried user is the current user',
                'selectable' => false, // Does not try to query this from the database
            ]
        ];
    }

    // You can also resolve a field by declaring a method in the class
    // with the following format resolve[FIELD_NAME]Field()
    protected function resolveEmailField($root, array $args)
    {
        return strtolower($root->email);
    }
}
```

The best practice is to start with your schema in `config/graphql.php` and add types directly to your schema (e.g. `default`):

```php
'schemas' => [
    'default' => [
        // ...
        
        'types' => [
            App\GraphQL\Types\UserType::class,
        ],
```

Alternatively you can:

- add the type on the "global" level, e.g. directly in the root config:
  ```php
  'types' => [
      App\GraphQL\Types\UserType::class,
  ],
  ```
  Adding them on the global level allows to share them between different schemas
  but be aware this might make it harder to understand which types/fields are used
  where.

- or add the type with the `GraphQL` Facade, in a service provider for example.
  ```php
  GraphQL::addType(\App\GraphQL\Types\UserType::class);
  ```

- or register multiple types at once with `addTypes`:
  ```php
  GraphQL::addTypes([
      \App\GraphQL\Types\UserType::class,
      'CustomName' => \App\GraphQL\Types\PostType::class,
  ]);
  ```
  Both indexed entries (class name auto-resolved) and associative entries
  (explicit name => class) are supported.

Then you need to define a query that returns this type (or a list). You can also specify arguments that you can use in the resolve method.
```php
declare(strict_types = 1);
namespace App\GraphQL\Queries;

use Closure;
use App\Models\User;
use Rebing\GraphQL\Support\Facades\GraphQL;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Query;

class UsersQuery extends Query
{
    protected $attributes = [
        'name' => 'users',
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::listOf(Type::nonNull(GraphQL::type('User'))));
    }

    public function args(): array
    {
        return [
            'id' => [
                'name' => 'id', 
                'type' => Type::string(),
            ],
            'email' => [
                'name' => 'email', 
                'type' => Type::string(),
            ]
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        if (isset($args['id'])) {
            return User::where('id' , $args['id'])->get();
        }

        if (isset($args['email'])) {
            return User::where('email', $args['email'])->get();
        }

        return User::all();
    }
}
```

Add the query to the `config/graphql.php` configuration file

```php
'schemas' => [
    'default' => [
        'query' => [
            App\GraphQL\Queries\UsersQuery::class
        ],
        // ...
    ]
]
```

And that's it. You should be able to query GraphQL with a POST request to the url `/graphql` (or anything you choose in your config). Try a POST request with the following `query` input

> **Note:** The `resolve()` method supports dependency injection for parameters
> beyond the first three (`$root`, `$args`, `$context`). You can typehint
> `Closure $getSelectFields` to receive a lazy factory, or typehint
> `SelectFields $fields` directly to get an eager-loaded instance. Any other
> class typehint will be resolved from Laravel's service container. See
> [Resolve method](#resolve-method) for full details.

```graphql
query FetchUsers {
    users {
        id
        email
    }
}
```

For example, using `curl`:
```bash
curl -X POST -H "Content-Type: application/json" \
  -d '{"query": "query FetchUsers { users { id email } }"}' \
  http://localhost:8000/graphql
```

### Creating a mutation

A mutation is like any other query. It accepts arguments and returns an object of a certain type. Mutations are meant to be used for operations **modifying** (mutating) the state on the server (which queries are not supposed to perform).

This is conventional abstraction, technically you can do anything you want in a query resolve, including mutating state.

For example, a mutation to update the password of a user. First you need to define the Mutation:

```php
declare(strict_types = 1);
namespace App\GraphQL\Mutations;

use Closure;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Rebing\GraphQL\Support\Facades\GraphQL;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Mutation;

class UpdateUserPasswordMutation extends Mutation
{
    protected $attributes = [
        'name' => 'updateUserPassword'
    ];

    public function type(): Type
    {
        return Type::nonNull(GraphQL::type('User'));
    }

    public function args(): array
    {
        return [
            'id' => [
                'name' => 'id', 
                'type' => Type::nonNull(Type::string()),
            ],
            'password' => [
                'name' => 'password', 
                'type' => Type::nonNull(Type::string()),
            ]
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $user = User::find($args['id']);
        if(!$user) {
            return null;
        }

        $user->password = Hash::make($args['password']);
        $user->save();

        return $user;
    }
}
```

As you can see in the `resolve()` method, you use the arguments to update your model and return it.

You should then add the mutation to the `config/graphql.php` configuration file:

```php
'schemas' => [
    'default' => [
        'mutation' => [
            App\GraphQL\Mutations\UpdateUserPasswordMutation::class,
        ],
        // ...
    ]
]
```

You can then use the following query on your endpoint to do the mutation:

```graphql
mutation users {
    updateUserPassword(id: "1", password: "newpassword") {
        id
        email
    }
}
```

For example, using `curl`:
```bash
curl -X POST -H "Content-Type: application/json" \
  -d '{"query": "mutation users { updateUserPassword(id: \"1\", password: \"newpassword\") { id email } }"}' \
  http://localhost:8000/graphql
```

#### File uploads

This library uses https://github.com/laragraph/utils which is compliant with the spec at https://github.com/jaydenseric/graphql-multipart-request-spec .

You have to add the `\Rebing\GraphQL\Support\UploadType` first to your `config/graphql` schema types definition (either global or in your schema):

```php
'types' => [
    \Rebing\GraphQL\Support\UploadType::class,
],
```

It is important that you send the request as `multipart/form-data`:

> **WARNING:** when you are uploading files, Laravel will use FormRequest - it means
> that middlewares which are changing request, will not have any effect.

```php
declare(strict_types = 1);
namespace App\GraphQL\Mutations;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class UserProfilePhotoMutation extends Mutation
{
    protected $attributes = [
        'name' => 'userProfilePhoto',
    ];

    public function type(): Type
    {
        return GraphQL::type('User');
    }

    public function args(): array
    {
        return [
            'profilePicture' => [
                'name' => 'profilePicture',
                'type' => GraphQL::type('Upload'),
                'rules' => ['required', 'image', 'max:1500'],
            ],
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $file = $args['profilePicture'];

        // Do something with file here...
    }
}
```

Note: You can test your file upload implementation using [Altair](https://altairgraphql.dev/) as explained [here](https://altairgraphql.dev/docs/features/file-upload).

##### Vue.js example

```vue
<template>
  <div>
    <input type="file" ref="fileInput" @change="handleFileChange" />
    <button :disabled="!file" @click="upload">Upload</button>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const file = ref(null);
const fileInput = ref(null);

function handleFileChange() {
  file.value = fileInput.value.files[0];
}

async function upload() {
  if (!file.value) return;

  const formData = new FormData();
  formData.set('operations', JSON.stringify({
    query: `mutation uploadSingleFile($file: Upload!) {
      upload_single_file(attachment: $file)
    }`,
    variables: { attachment: null },
  }));
  formData.set('map', JSON.stringify({ '0': ['variables.attachment'] }));
  formData.append('0', file.value);

  const response = await fetch('/graphql', {
    method: 'POST',
    body: formData,
  });

  const result = await response.json();

  if (!result.errors) {
    file.value = null;
  }
}
</script>
```

##### Vanilla JavaScript

```html
<input type="file" id="fileUpload">
```
```javascript
const fileInput = document.getElementById('fileUpload');
const file = fileInput.files[0];

const formData = new FormData();
formData.set('operations', JSON.stringify({
  query: `mutation uploadSingleFile($file: Upload!) {
    upload_single_file(attachment: $file)
  }`,
  variables: { attachment: null },
}));
formData.set('map', JSON.stringify({ '0': ['variables.attachment'] }));
formData.append('0', file);

const response = await fetch('/graphql', {
  method: 'POST',
  body: formData,
});
const result = await response.json();
```

### Validation

Laravel's validation is supported on queries, mutations, input types and field
arguments.

> **Note:** The support is "sugar on top" and is provided as a convenience.
> It may have limitations in certain cases, in which case regular Laravel
> validation can be used in your respective `resolve()` methods, just like
> in regular Laravel code.

Adding validation rules is supported in the following ways:

- the field configuration key `'rules'` is supported
  - in queries/mutations in fields declared in `function args()`
  - in input types in fields declared in `function fields()`  
  - `'args'` declared for a field
- Overriding `\Rebing\GraphQL\Support\Field::rules` on any query/mutation/input type
- Or directly use Laravel's `Validator` in your `resolve()` method

Using the configuration key `'rules'` is very convenient, as it is declared in
the same location as the GraphQL type itself. However, you may hit certain
restrictions with this approach (like multi-field validation using `*`), in
which case you can override the `rules()` method.

#### Example defining rules in each argument

```php
class UpdateUserEmailMutation extends Mutation
{
    //...

    public function args(): array
    {
        return [
            'id' => [
                'name' => 'id',
                'type' => Type::string(),
                'rules' => ['required']
            ],
            'email' => [
                'name' => 'email',
                'type' => Type::string(),
                'rules' => ['required', 'email']
            ]
        ];
    }

    //...
}
```

#### Example using the `rules()` method

```php
declare(strict_types = 1);
namespace App\GraphQL\Mutations;

use Closure;
use App\Models\User;
use Rebing\GraphQL\Support\Facades\GraphQL;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Mutation;

class UpdateUserEmailMutation extends Mutation
{
    protected $attributes = [
        'name' => 'updateUserEmail'
    ];

    public function type(): Type
    {
        return GraphQL::type('User');
    }

    public function args(): array
    {
        return [
            'id' => [
                'name' => 'id', 
                'type' => Type::string(),
            ],
            'email' => [
                'name' => 'email', 
                'type' => Type::string(),
            ]
        ];
    }

    protected function rules(array $args = []): array
    {
        return [
            'id' => ['required'],
            'email' => ['required', 'email'],
            'password' => $args['id'] !== 1337 ? ['required'] : [],
        ];
    }

    public function resolve($root, array $args)
    {
        $user = User::find($args['id']);
        if (!$user) {
            return null;
        }

        $user->email = $args['email'];
        $user->save();

        return $user;
    }
}
```

#### Example using Laravel's validator directly

Calling `validate()` in the example below will throw Laravel's `ValidationException`
which is handed by the default `error_formatter` by this library:

```php
protected function resolve($root, array $args) {
    \Illuminate\Support\Facades\Validator::make($args, [
        'data.*.password' => 'string|nullable|same:data.*.password_confirmation',
    ])->validate();
}
```

The format of the `'rules'` configuration key, or the rules returned by the
`rules()` method, follows the same convention that Laravel supports, e.g.:
- `'rules' => 'required|string'`\
  or
- `'rules' => ['required', 'string']`\
  or
- `'rules' => function (…) { … }`\
  etc.

For the `args()` method or the `'args'` definition for a field, the field names
are directly used for the validation. However, for input types, which can be
nested and occur multiple times, the field names are mapped as e.g.
`data.0.fieldname`. This is important to understand when returning rules from
the `rules()` method.

#### Handling validation errors

Exceptions are used to communicate back in the GraphQL response that validation
errors occurred. When using the built-in support, the exception
`\Rebing\GraphQL\Error\ValidationError` is thrown. In your custom code or when
directly using the Laravel `Validator`, Laravel's built-in
`\Illuminate\Validation\ValidationException` is supported too. In both cases,
the GraphQL response is transformed to the error format shown below.

To support returning validation errors in a GraphQL error response, the
`'extensions'` are used, as there's no proper equivalent.

On the client side, you can check if `message` for a given error matches
`'validation'`, you can expect the `extensions.validation` key which maps each
field to their respective errors:

```json
{
  "data": {
    "updateUserEmail": null
  },
  "errors": [
    {
      "message": "validation",
      "extensions": {
        "validation": {
          "email": [
            "The email is invalid."
          ]
        }
      },
      "locations": [
        {
          "line": 1,
          "column": 20
        }
      ]
    }
  ]
}
```

You can customize the way this is handled by providing your own `error_formatter`
in the configuration, replacing the default one from this library.

#### Customizing error messages

The validation errors returned can be customised by overriding the
`validationErrorMessages` method. This method should return an array of custom
validation messages in the same way documented by Laravel's validation. For
example, to check an `email` argument doesn't conflict with any existing data,
you could perform the following:

> **Note:** the keys should be in `field_name`.`validator_type` format, so you can
> return specific errors per validation type.

```php
public function validationErrorMessages(array $args = []): array
{
    return [
        'name.required' => 'Please enter your full name',
        'name.string' => 'Your name must be a valid string',
        'email.required' => 'Please enter your email address',
        'email.email' => 'Please enter a valid email address',
        'email.exists' => 'Sorry, this email address is already in use',
    ];
}
```

#### Customizing attributes

The validation attributes can be customised by overriding the
`validationAttributes` method. This method should return an array of custom
attributes in the same way documented by Laravel's validation.

```php
public function validationAttributes(array $args = []): array
{
    return [
        'email' => 'email address',
    ];
}
```

#### Cross-field validation rules in nested input types

When using Laravel validation rules that reference sibling fields (like
`prohibits`, `required_without`, `required_if`, etc.) within an InputType, the
library automatically transforms those references into fully-qualified
dot-notation paths that Laravel's Validator can resolve correctly.

For example, given an InputType:

```php
class RecipientInput extends InputType
{
    protected $attributes = ['name' => 'RecipientInput'];

    public function fields(): array
    {
        return [
            'createParams' => [
                'type' => Type::string(),
                'rules' => ['nullable', 'prohibits:mintParams'],
            ],
            'mintParams' => [
                'type' => Type::string(),
                'rules' => ['nullable', 'prohibits:createParams'],
            ],
        ];
    }
}
```

Used in a mutation as a list:

```php
public function args(): array
{
    return [
        'recipients' => [
            'type' => Type::nonNull(Type::listOf(Type::nonNull(GraphQL::type('RecipientInput')))),
        ],
    ];
}
```

The `prohibits:mintParams` rule on `recipients.0.createParams` is automatically
transformed to `prohibits:recipients.0.mintParams` so that Laravel's Validator
correctly resolves the sibling field reference.

This applies to all dependent rules including `prohibits`, `required_with`,
`required_with_all`, `required_without`, `required_without_all`, `present_with`,
`present_with_all`, `missing_with`, `missing_with_all`, `exclude_with`,
`exclude_without`, `same`, `different`, `required_if`, `required_unless`,
`prohibited_if`, `prohibited_unless`, `exclude_if`, `exclude_unless`,
`accepted_if`, `declined_if`, `present_if`, `present_unless`, `missing_if`,
`missing_unless`, `required_if_accepted`, `required_if_declined`,
`prohibited_if_accepted`, `prohibited_if_declined`, and comparison rules like
`gt`, `gte`, `lt`, `lte`, `before`, `after`, `before_or_equal`, `after_or_equal`
(when they reference a sibling field). For rules like `required_if` that take
both a field reference and a value (e.g. `required_if:mode,advanced`), only the
field reference parameter is transformed. See `RulesPrefixer` for the full list.

**Disabling automatic prefixing:** If you need to opt out of this behavior for
a specific query or mutation, override `processCollectedRules()`:

```php
class MyMutation extends Mutation
{
    protected function processCollectedRules(array $rules): array
    {
        return $rules; // disable automatic cross-field rule prefixing
    }
}
```

#### Misc notes

Certain type declarations of GraphQL may cancel our or render certain validations
unnecessary. A good example is using `Type::nonNull()` to ultimately declare
that an argument is required. In such a case a `'rules' => 'required'`
configuration will likely never be triggered, because the GraphQL execution
engine already prevents this field from being accepted in the first place.

Or to be more clear: if a GraphQL type system violation occurs, then no Laravel
validation will be even executed, as the code does not get so far.

### Resolve method

The resolve method is used in both queries and mutations, and it's here that responses are created.

The first three parameters to the resolve method are hard-coded:

1. The `$root` object this resolve method belongs to (can be `null`)
2. The arguments passed as `array $args` (can be an empty array)
3. The query specific GraphQL context
   Can be customized by implementing a custom "execution middleware", see
   `\Rebing\GraphQL\Support\ExecutionMiddleware\AddAuthUserContextValueMiddleware`
   for an example.

Arguments here after will be attempted to be injected, similar to how controller methods works in Laravel.

You can typehint any class that you will need an instance of.

There are two hardcoded classes which depend on the local data for the query:
- `GraphQL\Type\Definition\ResolveInfo` has information useful for field resolution process.
- `Rebing\GraphQL\Support\SelectFields` allows eager loading of related Eloquent models, see [Eager loading relationships](#eager-loading-relationships).

Example:

```php
declare(strict_types = 1);
namespace App\GraphQL\Queries;

use Closure;
use App\Models\User;
use Rebing\GraphQL\Support\Facades\GraphQL;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\SelectFields;
use Rebing\GraphQL\Support\Query;
use SomeClassNamespace\SomeClassThatDoLogging;

class UsersQuery extends Query
{
    protected $attributes = [
        'name' => 'users',
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('User'));
    }

    public function args(): array
    {
        return [
            'id' => [
                'name' => 'id', 
                'type' => Type::string(),
            ]
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $info, SelectFields $fields, SomeClassThatDoLogging $logging)
    {
        $logging->log('fetched user');

        $select = $fields->getSelect();
        $with = $fields->getRelations();

        $users = User::select($select)->with($with);

        return $users->get();
    }
}
```

### Resolver middleware

These are **GraphQL specific resolver middlewares** and are only
conceptually related to Laravel's "HTTP middleware". The main difference:

- Laravel's HTTP middleware:
  - works on the schema / route level
  - is compatible with any regular Laravel HTTP middleware
  - is the same for all queries/mutations in a schema
- Resolver middleware
  - Works similar in concept
  - But applies on the query/mutation level, i.e. can be different for every
    query/mutation
  - Is technically not compatible with HTTP middleware
  - Takes different arguments

#### Defining middleware

To create a new middleware, use the `make:graphql:middleware` Artisan command

```sh
php artisan make:graphql:middleware ResolvePage
```

This command will place a new ResolvePage class within your app/GraphQL/Middleware directory.
In this middleware, we will set the Paginator current page to the argument we accept via our `PaginationType`:

```php
declare(strict_types = 1);
namespace App\GraphQL\Middleware;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Pagination\Paginator;
use Rebing\GraphQL\Support\Middleware;

class ResolvePage extends Middleware
{
    public function handle($root, array $args, $context, ResolveInfo $info, Closure $next)
    {
        Paginator::currentPageResolver(function () use ($args) {
            return $args['pagination']['page'] ?? 1;
        });

        return $next($root, $args, $context, $info);
    }
}
```

#### Registering middleware

If you would like to assign middleware to specific queries/mutations,
list the middleware class in the `$middleware` property of your query class.

```php
declare(strict_types = 1);
namespace App\GraphQL\Queries;

use App\GraphQL\Middleware;
use Rebing\GraphQL\Support\Query;

class UsersQuery extends Query
{
    protected $middleware = [
        Middleware\Logstash::class,
        Middleware\ResolvePage::class,
    ];
}
```

If you want a middleware to run during every GraphQL query/mutation to your application,
list the middleware class in the `$middleware` property of your base query class.

```php
declare(strict_types = 1);
namespace App\GraphQL\Queries;

use App\GraphQL\Middleware;
use Rebing\GraphQL\Support\Query as BaseQuery;

abstract class Query extends BaseQuery
{
    protected $middleware = [
        Middleware\Logstash::class,
        Middleware\ResolvePage::class,
    ];
}
```

Alternatively, you can override `getMiddleware` to supply your own logic:

```php
    protected function getMiddleware(): array
    {
        return array_merge([...], $this->middleware);
    }
```

If you want to register middleware globally, use the `resolver_middleware_append` key in `config/graphql.php` (defaults to `null`, treated as an empty array):

```php  
return [
    ...
    'resolver_middleware_append' => [YourMiddleware::class],
];
```

You can also use the `appendGlobalResolverMiddleware` method in any ServiceProvider:

```php
    ...
    public function boot()
    {
        ...
        GraphQL::appendGlobalResolverMiddleware(YourMiddleware::class);
        // Or with new instance
        GraphQL::appendGlobalResolverMiddleware(new YourMiddleware(...));
    }
```

If your middleware needs to wrap **all** other resolver middleware (including
per-field middleware), use `prependGlobalResolverMiddleware` instead:

```php
GraphQL::prependGlobalResolverMiddleware(YourOutermostMiddleware::class);
```

The resulting pipeline order is: prepended global middleware, per-field
middleware, appended global middleware. This is used internally by the tracing
system but is available for any middleware that must run outermost.

#### Terminable middleware

Sometimes a middleware may need to do some work after the response has been sent to the browser.
If you define a terminate method on your middleware and your web server is using FastCGI,
the terminate method will automatically be called after the response is sent to the browser:

```php
declare(strict_types = 1);
namespace App\GraphQL\Middleware;

use Countable;
use GraphQL\Language\Printer;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Rebing\GraphQL\Support\Middleware;

class Logstash extends Middleware
{
    public function terminate($field, array $args, $context, ResolveInfo $info, $result): void
    {
        Log::channel('logstash')->info('', (
            collect([
                'query' => $info->fieldName,
                'operation' => $info->operation->name->value ?? null,
                'type' => $info->operation->operation,
                'fields' => array_keys(Arr::dot($info->getFieldSelection($depth = PHP_INT_MAX))),
                'schema' => Arr::first(Route::current()->parameters()) ?? Config::get('graphql.default_schema', 'default'),
                'vars' => $this->formatVariableDefinitions($info->operation->variableDefinitions),
            ])
                ->when($result instanceof Countable, function ($metadata) use ($result) {
                    return $metadata->put('count', $result->count());
                })
                ->when($result instanceof AbstractPaginator, function ($metadata) use ($result) {
                    return $metadata->put('per_page', $result->perPage());
                })
                ->when($result instanceof LengthAwarePaginator, function ($metadata) use ($result) {
                    return $metadata->put('total', $result->total());
                })
                ->merge($this->formatArguments($args))
                ->toArray()
        ));
    }

    private function formatArguments(array $args): array
    {
        return collect(Arr::sanitize($args))
            ->mapWithKeys(function ($value, $key) {
                return ["\${$key}" => $value];
            })
            ->toArray();
    }

    private function formatVariableDefinitions(?iterable $variableDefinitions = []): array
    {
        return collect($variableDefinitions)
            ->map(function ($def) {
                return Printer::doPrint($def);
            })
            ->toArray();
    }
}
```

The terminate method receives both the resolver arguments and the query result.

Once you have defined a terminable middleware, you should add it to the list of
middleware in your queries and mutations.

### Authorization

For authorization similar to Laravel's Request (or middleware) functionality, we can override the `authorize()` function in a Query or Mutation.

> **Important:** The `authorize()` method must return exactly `true` (strict comparison) for the request to proceed. Returning other truthy values (e.g. `1`, `"yes"`) will be treated as unauthorized.

> **Note:** Authorization is checked **before** validation rules are evaluated. This prevents unauthenticated users from probing validation rules to discover API structure.

An example of Laravel's `'auth'` middleware:

```php
declare(strict_types = 1);
namespace App\GraphQL\Queries;

use Illuminate\Support\Facades\Auth;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;

class UsersQuery extends Query
{
    public function authorize($root, array $args, $ctx, ResolveInfo $resolveInfo = null, Closure $getSelectFields = null): bool
    {
        // true, if logged in
        return ! Auth::guest();
    }

    // ...
}
```

Or we can make use of arguments passed via the GraphQL query:

```php
declare(strict_types = 1);
namespace App\GraphQL\Queries;

use Illuminate\Support\Facades\Auth;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;

class UsersQuery extends Query
{
    public function authorize($root, array $args, $ctx, ResolveInfo $resolveInfo = null, Closure $getSelectFields = null): bool
    {
        if (isset($args['id'])) {
            return Auth::id() == $args['id'];
        }

        return true;
    }

    // ...
}
```

You can also provide a custom error message when the authorization fails (defaults to Unauthorized):

```php
declare(strict_types = 1);
namespace App\GraphQL\Queries;

use Illuminate\Support\Facades\Auth;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;

class UsersQuery extends Query
{
    public function authorize($root, array $args, $ctx, ResolveInfo $resolveInfo = null, Closure $getSelectFields = null): bool
    {
        if (isset($args['id'])) {
            return Auth::id() == $args['id'];
        }

        return true;
    }

    public function getAuthorizationMessage(): string
    {
        return 'You are not authorized to perform this action';
    }

    // ...
}
```

### Privacy

You can set custom privacy attributes for every Type's Field. If a field is not
allowed, `null` will be returned. Privacy is enforced at the field resolver
level, so it works universally - whether the type is a root query result, a
nested sub-type, or accessed via `SelectFields`.

The privacy callback receives two arguments: the **field's own arguments**
(`$args`) and the **query context** (`$ctx`).

**Using a closure:**

```php
use Illuminate\Support\Facades\Auth;

class UserType extends GraphQLType
{
    // ...

    public function fields(): array
    {
        return [
            'id' => [
                'type'          => Type::nonNull(Type::string()),
                'description'   => 'The id of the user',
            ],
            'email' => [
                'type'          => Type::string(),
                'description'   => 'The email of user',
                'privacy'       => function (array $args, $ctx): bool {
                    // Only the authenticated user can see their own email.
                    // $ctx is the query context value (see notes below).
                    // By default, AddAuthUserContextValueMiddleware sets
                    // $ctx to the authenticated user model directly.
                    return $ctx && $ctx->id === Auth::id();
                },
            ],
        ];
    }

    // ...
}
```

**Using a Privacy class:**

You can also create a class that extends the abstract `Privacy` class:

```php
use Illuminate\Support\Facades\Auth;
use Rebing\GraphQL\Support\Privacy;

class MePrivacy extends Privacy
{
    public function validate(array $fieldArgs, $queryContext = null): bool
    {
        return $queryContext && $queryContext->id === Auth::id();
    }
}
```

Then reference it by class name on the field:

```php
use MePrivacy;

class UserType extends GraphQLType
{
    // ...

    public function fields(): array
    {
        return [
            'id' => [
                'type'          => Type::nonNull(Type::string()),
                'description'   => 'The id of the user',
            ],
            'email' => [
                'type'          => Type::string(),
                'description'   => 'The email of user',
                'privacy'       => MePrivacy::class,
            ],
        ];
    }

    // ...
}
```

**Using field arguments in a privacy check:**

If the field declares its own `args`, they are available in `$args`:

```php
'ssn' => [
    'type'    => Type::string(),
    'args'    => [
        'reason' => [
            'type' => Type::nonNull(Type::string()),
        ],
    ],
    'privacy' => function (array $args, $ctx): bool {
        // Only allow access when a valid reason is provided.
        return in_array($args['reason'] ?? '', ['legal', 'compliance']);
    },
],
```

> **`$args` - field arguments, not query arguments.** The `$args` parameter
> contains the arguments declared on the field itself (via the `args` key). If
> the field declares no arguments, `$args` will be an empty array. These are
> *not* the root query/mutation arguments.

> **`$ctx` - the query context value.** This is the context value passed to
> the GraphQL execution. By default, the built-in
> `AddAuthUserContextValueMiddleware` execution middleware sets this directly to
> the authenticated user model (i.e. `Auth::user()`), or `null` if no user is
> authenticated. You can customize the context via your own execution middleware.

> **Privacy vs Authorization.** `authorize()` on a Query or Mutation gates the
> *entire* operation - if it fails, the whole request is rejected with an error.
> `privacy` on a Type field gates *individual fields* and silently returns
> `null` when denied. Use `authorize()` for access control on operations and
> `privacy` for field-level visibility within types.

> **Caution with non-null fields.** When privacy denies access, the field
> resolver returns `null`. If the field is typed as `Type::nonNull(...)`, this
> `null` violates the GraphQL non-null contract and causes an error that
> propagates up to the nearest nullable parent. Always use nullable types for
> privacy-protected fields.

### Query variables

GraphQL offers you the possibility to use variables in your query so you don't need to "hardcode" value. This is done like that:

```graphql
query FetchUserByID($id: String)
{
    user(id: $id) {
        id
        email
    }
}
```

When you query the GraphQL endpoint, you can pass a JSON encoded `variables` parameter.

For example, using `curl`:
```bash
curl -X POST -H "Content-Type: application/json" \
  -d '{"query": "query FetchUserByID($id: Int) { user(id: $id) { id email } }", "variables": {"id": 123}}' \
  http://localhost:8000/graphql
```

### Custom field

You can also define a field as a class if you want to reuse it in multiple types.

```php
declare(strict_types = 1);
namespace App\GraphQL\Fields;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Field;

class PictureField extends Field
{
    protected $attributes = [
        'description'   => 'A picture',
    ];

    public function type(): Type
    {
        return Type::string();
    }

    public function args(): array
    {
        return [
            'width' => [
                'type' => Type::int(),
                'description' => 'The width of the picture'
            ],
            'height' => [
                'type' => Type::int(),
                'description' => 'The height of the picture'
            ]
        ];
    }

    protected function resolve($root, array $args)
    {
        $width = isset($args['width']) ? $args['width']:100;
        $height = isset($args['height']) ? $args['height']:100;

        return 'https://placehold.co/'.$width.'x'.$height;
    }
}
```

You can then use it in your type declaration

```php
declare(strict_types = 1);
namespace App\GraphQL\Types;

use App\GraphQL\Fields\PictureField;
use App\Models\User;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class UserType extends GraphQLType
{
    protected $attributes = [
        'name'          => 'User',
        'description'   => 'A user',
        'model'         => User::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The id of the user'
            ],
            'email' => [
                'type' => Type::string(),
                'description' => 'The email of user'
            ],
            //Instead of passing an array, you pass a class path to your custom field
            'picture' => PictureField::class
        ];
    }
}
```

#### Even better reusable fields

Instead of using the class name, you can also supply an actual instance of the `Field`. This allows you to not only re-use the field, but will also open up the possibility to re-use the resolver.

Let's imagine we want a field type that can output dates formatted in all sorts of ways.

```php
declare(strict_types = 1);
namespace App\GraphQL\Fields;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Field;

class FormattableDate extends Field
{
    protected $attributes = [
        'description' => 'A field that can output a date in all sorts of ways.',
    ];

    public function __construct(array $settings = [])
    {
        $this->attributes = \array_merge($this->attributes, $settings);
    }

    public function type(): Type
    {
        return Type::string();
    }

    public function args(): array
    {
        return [
            'format' => [
                'type' => Type::string(),
                'defaultValue' => 'Y-m-d H:i',
                'description' => 'Defaults to Y-m-d H:i',
            ],
            'relative' => [
                'type' => Type::boolean(),
                'defaultValue' => false,
            ],
        ];
    }

    protected function resolve($root, array $args): ?string
    {
        $date = $root->{$this->getProperty()};

        if (!$date instanceof Carbon) {
            return null;
        }

        if ($args['relative']) {
            return $date->diffForHumans();
        }

        return $date->format($args['format']);
    }

    protected function getProperty(): string
    {
        return $this->attributes['alias'] ?? $this->attributes['name'];
    }
}
```

You can use this field in your type as follows:

```php
declare(strict_types = 1);
namespace App\GraphQL\Types;

use App\GraphQL\Fields\FormattableDate;
use App\Models\User;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class UserType extends GraphQLType
{
    protected $attributes = [
        'name'          => 'User',
        'description'   => 'A user',
        'model'         => User::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The id of the user'
            ],
            'email' => [
                'type' => Type::string(),
                'description' => 'The email of user'
            ],

            // You can simply supply an instance of the class
            'dateOfBirth' => new FormattableDate,

            // Because the constructor of `FormattableDate` accepts our the array of parameters,
            // we can override them very easily.
            // Imagine we want our field to be called `createdAt`, but our database column
            // is called `created_at`:
            'createdAt' => new FormattableDate([
                'alias' => 'created_at',
            ])
        ];
    }
}
```

### Eager loading relationships

The `Rebing\GraphQL\Support\SelectFields` class allows to eager load related Eloquent models. 
Only the required fields will be queried from the database.

The class can be instantiated by **typehinting** `SelectFields $selectField` in your resolve method.

You can also construct the class by typehinting a `Closure`.
The Closure accepts an optional parameter for the depth of the query to analyse.

Your Query would look like:

```php
declare(strict_types = 1);
namespace App\GraphQL\Queries;

use Closure;
use App\Models\User;
use Rebing\GraphQL\Support\Facades\GraphQL;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\SelectFields;
use Rebing\GraphQL\Support\Query;

class UsersQuery extends Query
{
    protected $attributes = [
        'name' => 'users',
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('User'));
    }

    public function args(): array
    {
        return [
            'id' => [
                'name' => 'id', 
                'type' => Type::string(),
            ],
            'email' => [
                'name' => 'email', 
                'type' => Type::string(),
            ]
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $info, Closure $getSelectFields)
    {
        /** @var SelectFields $fields */
        $fields = $getSelectFields();
        $select = $fields->getSelect();
        $with = $fields->getRelations();

        $users = User::select($select)->with($with);

        return $users->get();
    }
}
```

Your Type for User might look like shown below. The `profile` and `posts`
relations must also exist in the UserModel's relations. If some fields are
required for the relation to load or validation etc, then you can define an
`always` attribute that will add the given attributes to select.

The attribute can be a comma separated string or an array of attributes to
always include.

```php
// Array form:
'always' => ['title', 'body'],
// String form (comma-separated):
'always' => 'title,body',
```

```php
declare(strict_types = 1);
namespace App\GraphQL\Types;

use App\Models\User;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class UserType extends GraphQLType
{
    /**
     * @var array
     */
    protected $attributes = [
        'name'          => 'User',
        'description'   => 'A user',
        'model'         => User::class,
    ];

    /**
    * @return array
    */
    public function fields(): array
    {
        return [
            'uuid' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The uuid of the user'
            ],
            'email' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The email of user'
            ],
            'profile' => [
                'type' => GraphQL::type('Profile'),
                'description' => 'The user profile',
            ],
            'posts' => [
                'type' => Type::listOf(GraphQL::type('Post')),
                'description' => 'The user posts',
                // Can also be defined as a string
                'always' => ['title', 'body'],
            ]
        ];
    }
}
```

At this point we have a profile and a post type as expected for any model

```php
class ProfileType extends GraphQLType
{
    protected $attributes = [
        'name'          => 'Profile',
        'description'   => 'A user profile',
        'model'         => UserProfileModel::class,
    ];

    public function fields(): array
    {
        return [
            'name' => [
                'type' => Type::string(),
                'description' => 'The name of user'
            ]
        ];
    }
}
```

```php
class PostType extends GraphQLType
{
    protected $attributes = [
        'name'          => 'Post',
        'description'   => 'A post',
        'model'         => PostModel::class,
    ];

    public function fields(): array
    {
        return [
            'title' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The title of the post'
            ],
            'body' => [
                'type' => Type::string(),
                'description' => 'The body the post'
            ]
        ];
    }
}
```

### Type relationship query

> **Note:** this only applies when making use of the `SelectFields` class to query Eloquent models!

You can also specify the `query` that will be included with a relationship via Eloquent's query builder:

```php
class UserType extends GraphQLType
{

    // ...

    public function fields(): array
    {
        return [
            // ...

            // Relation
            'posts' => [
                'type'          => Type::listOf(GraphQL::type('Post')),
                'description'   => 'A list of posts written by the user',
                'args'          => [
                    'date_from' => [
                        'type' => Type::string(),
                    ],
                 ],
                // $args are the local arguments passed to the relation
                // $query is the relation builder object
                // $ctx is the GraphQL context (customizable via execution middleware)
                // The return value should be the query builder or void
                'query'         => function (array $args, $query, $ctx): void {
                    $query->addSelect('some_column')
                          ->where('posts.created_at', '>', $args['date_from']);
                }
            ]
        ];
    }
}
```

### Pagination

Pagination will be used, if a query or mutation returns a `PaginationType`.

Note that unless you use [resolver middleware](#defining-middleware),
you will have to manually supply both the limit and page values:

```php
declare(strict_types = 1);
namespace App\GraphQL\Queries;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class PostsQuery extends Query
{
    public function type(): Type
    {
        return GraphQL::paginate('posts');
    }

    // ...

    public function resolve($root, array $args, $context, ResolveInfo $info, Closure $getSelectFields)
    {
        $fields = $getSelectFields();

        return Post::with($fields->getRelations())
            ->select($fields->getSelect())
            ->paginate($args['limit'], ['*'], 'page', $args['page']);
    }
}
```

Query `posts(limit:10,page:1){data{id},total,per_page}` might return

```json
{
    "data": {
        "posts": {
            "data": [
                {"id": 3},
                {"id": 5}
            ],
            "total": 21,
            "per_page": 10
        }
    }
}
```

Note that you need to add in the extra 'data' object when you request paginated resources as the returned data gives you
the paginated resources in a data object at the same level as the returned pagination metadata.

[Simple Pagination](https://laravel.com/docs/pagination#simple-pagination) will be used, if a query or mutation returns a `SimplePaginationType`.

```php
declare(strict_types = 1);
namespace App\GraphQL\Queries;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class PostsQuery extends Query
{
    public function type(): Type
    {
        return GraphQL::simplePaginate('posts');
    }

    // ...

    public function resolve($root, array $args, $context, ResolveInfo $info, Closure $getSelectFields)
    {
        $fields = $getSelectFields();

        return Post::with($fields->getRelations())
            ->select($fields->getSelect())
            ->simplePaginate($args['limit'], ['*'], 'page', $args['page']);
    }
}
```

`SimplePaginationType` exposes the following fields: `data` (the paginated items),
`per_page`, `current_page`, `from`, `to`, and `has_more_pages`. Unlike full
pagination, `total` and `last_page` are **not** available.

[Cursor Pagination](https://laravel.com/docs/pagination#cursor-pagination) will be used, if a query or mutation returns a `CursorPaginationType`.

```php
declare(strict_types = 1);
namespace App\GraphQL\Queries;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class PostsQuery extends Query
{
    public function type(): Type
    {
        return GraphQL::cursorPaginate('posts');
    }

    // ...

    public function resolve($root, array $args, $context, ResolveInfo $info, Closure $getSelectFields)
    {
        $fields = $getSelectFields();

        return Post::with($fields->getRelations())
            ->select($fields->getSelect())
            ->cursorPaginate($args['limit'], ['*'], 'cursorName', $args['cursor']);
    }
}
```

`CursorPaginationType` exposes the following fields: `data` (the paginated
items), `per_page`, `previous_cursor` (`String`, nullable), and `next_cursor`
(`String`, nullable).

> **Note:** If you use a custom pagination class via the `pagination_type`,
> `simple_pagination_type`, or `cursor_pagination_type` config keys, your class
> must implement `\Rebing\GraphQL\Support\Contracts\WrapType` for
> `SelectFields` to work correctly. The built-in pagination types already
> implement this interface. See [Wrap Types](#wrap-types) for more details.

### Batching

Batched requests are required to be sent via a POST request.

You can send multiple queries (or mutations) at once by grouping them together. Therefore, instead of creating two HTTP requests:

```
POST
{
    query: "query postsQuery { posts { id, comment, author_id } }"
}

POST
{
    query: "mutation storePostMutation($comment: String!) { store_post(comment: $comment) { id } }",
    variables: { "comment": "Hi there!" }
}
```

you could batch it as one

```
POST
[
    {
        query: "query postsQuery { posts { id, comment, author_id } }"
    },
    {
        query: "mutation storePostMutation($comment: String!) { store_post(comment: $comment) { id } }",
        variables: { "comment": "Hi there!" }
    }
]
```

For systems sending multiple requests at once, this can help performance by batching together queries that will be made within a certain interval of time.

There are tools that help with this and can handle the batching for you, e.g. [Apollo](https://www.apollographql.com/)

> **A note on query batching:** whilst it may look like an "only win" situations,
> there are possible downsides using batching:
> 
> - All queries/mutations are executed in the same "process execution context".  
>   If your code has side-effects which might not show up in the usual FastCGI
>   environment (single request/response), it may cause issues here.
> 
> - The "HTTP middleware" is only executed for the whole batch _once_  
>   In case you would expect it being triggered for each query/mutation included.
>   This may be especially relevant for logging or rate limiting.  
>   OTOH with "resolver middleware" this will work as expected (though the solve
>   different problems).
> 
> - Batch size limits  
>   By default, a maximum of 10 operations per batch is enforced via the
>   `batching.max_batch_size` config option. Set to `null` for no limit.

Support for batching can be enabled by setting the config `batching.enable` to `true` (disabled by default).

The maximum number of operations per batch is controlled by `batching.max_batch_size` (default: `10`). Requests exceeding this limit will receive an error response. Set to `null` to allow unlimited operations (not recommended).

### Scalar types

GraphQL comes with built-in scalar types for string, int, boolean, etc. It's possible to create custom scalar types to special purpose fields.

An example could be a link: instead of using `Type::string()` you could create a scalar type `Link` and reference it with `GraphQL::type('Link')`.

The benefits would be:

- a dedicated description so you can give more meaning/purpose to a field than just call it a string type
- explicit conversion logic for the following steps:
  - converting from the internal logic to the serialized GraphQL output (`serialize`)
  - query/field input argument conversion (`parseLiteral`)
  - when passed as variables to your query (`parseValue`)

This also means validation logic can be added within these methods to _ensure_ that the value delivered/received is e.g. a true link.

A scalar type has to implement all the methods; you can quick start this with `artisan make:graphql:scalar <typename>`. Then just add the scalar to your existing types in the schema.

For more advanced use, please [refer to the official documentation regarding scalar types](https://webonyx.github.io/graphql-php/type-system/scalar-types).

> **A note on performance:** be mindful of the code you include in your scalar
> types methods. If you return a large number of fields making use of custom
> scalars which includes complex logic to validate field, it might impact your
> response times.

### Enums

Enumeration types are a special kind of scalar that is restricted to a particular set of allowed values.
Read more about Enums [here](https://graphql.org/learn/schema/#enumeration-types)

First create an Enum as an extension of the GraphQLType class:
```php
declare(strict_types = 1);
namespace App\GraphQL\Enums;

use Rebing\GraphQL\Support\EnumType;

class EpisodeEnum extends EnumType
{
    protected $attributes = [
        'name' => 'episode',
        'description' => 'The types of demographic elements',
        'values' => [
            'NEWHOPE' => 'NEWHOPE',
            'EMPIRE' => 'EMPIRE',
            'JEDI' => 'JEDI',
        ],
    ];
}
```

> **Note:** within the `$attributes['values']` array the key is enum value the GraphQL client
> will be able to choose from, while the value is what will your server receive (what will enum
> be resolved to).

The Enum will be registered like any other type in your schema in `config/graphql.php`:

```php
'schemas' => [
    'default' => [
        'types' => [
            EpisodeEnum::class,
        ],
```

Then use it like:
```php
declare(strict_types = 1);
namespace App\GraphQL\Types;

use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class TestType extends GraphQLType
{
    public function fields(): array
    {
        return [
            'episode_type' => [
                'type' => GraphQL::type('episode')
            ]
        ];
    }
}
```

### SelectFields and abstract types

When using `SelectFields` with union or interface types, custom `query`
callbacks on relation fields defined in member/concrete types are supported.
`SelectFields` will match the concrete type at eager-load time and apply the
callback automatically.

**Note:** When a query includes inline fragments on multiple member types that
each request different relations, `SelectFields` will merge all requested
relations into the eager-load set. This is a known limitation of how
`SelectFields` handles abstract types.

### Unions

A Union is an abstract type that simply enumerates other Object Types. The value of Union Type is actually a value of one of included Object Types.

It's useful if you need to return unrelated types in the same Query. For example when implementing a search for multiple different entities.

Example for defining a UnionType:

```php
declare(strict_types = 1);
namespace App\GraphQL\Unions;

use App\Post;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\UnionType;

class SearchResultUnion extends UnionType
{
    protected $attributes = [
        'name' => 'searchResult',
    ];

    public function types(): array
    {
        return [
            GraphQL::type('Post'),
            GraphQL::type('Episode'),
        ];
    }

    public function resolveType($value)
    {
        if ($value instanceof Post) {
            return GraphQL::type('Post');
        } elseif ($value instanceof Episode) {
            return GraphQL::type('Episode');
        }
    }
}

```

### Interfaces

You can use interfaces to abstract a set of fields. Read more about Interfaces [here](https://graphql.org/learn/schema/#interfaces)

An implementation of an interface:

```php
declare(strict_types = 1);
namespace App\GraphQL\Interfaces;

use Rebing\GraphQL\Support\Facades\GraphQL;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\InterfaceType;

class CharacterInterface extends InterfaceType
{
    protected $attributes = [
        'name' => 'character',
        'description' => 'Character interface.',
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'The id of the character.'
            ],
            'name' => Type::string(),
            'appearsIn' => [
                'type' => Type::nonNull(Type::listOf(GraphQL::type('Episode'))),
                'description' => 'A list of episodes in which the character has an appearance.'
            ],
        ];
    }

    public function resolveType($root)
    {
        // Use the resolveType to resolve the Type which is implemented trough this interface
        $type = $root['type'];
        if ($type === 'human') {
            return GraphQL::type('Human');
        } elseif  ($type === 'droid') {
            return GraphQL::type('Droid');
        }
    }
}
```

A Type that implements an interface:

```php
declare(strict_types = 1);
namespace App\GraphQL\Types;

use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;
use GraphQL\Type\Definition\Type;

class HumanType extends GraphQLType
{
    protected $attributes = [
        'name' => 'human',
        'description' => 'A human.'
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'The id of the human.',
            ],
            'name' => Type::string(),
            'appearsIn' => [
                'type' => Type::nonNull(Type::listOf(GraphQL::type('Episode'))),
                'description' => 'A list of episodes in which the human has an appearance.'
            ],
            'totalCredits' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'The total amount of credits this human owns.'
            ]
        ];
    }

    public function interfaces(): array
    {
        return [
            GraphQL::type('Character')
        ];
    }
}
```

#### Supporting custom queries on interface relations

If an interface contains a relation with a custom query, it's required to implement `public function types()` returning an array of `GraphQL::type()`, i.e. all the possible types it may resolve to (quite similar as it works for unions) so that it works correctly with `SelectFields`.

Additionally, if your query uses inline fragments to select fields that only exist on concrete types (e.g. `...on Post { created_at }`), implementing `types()` is required so that `SelectFields` can look up those fields from the concrete types.

Based on the previous code example, the method would look like:
```php
    public function types(): array
    {
        return[
            GraphQL::type('Human'),
            GraphQL::type('Droid'),
        ];
    }
```

#### Sharing interface fields

Since you often have to repeat many of the field definitions of the Interface in the concrete types, it makes sense to share the definitions of the Interface.
You can access and reuse specific interface fields with the method `getField(string fieldName): FieldDefinition`. To get all fields as an array use `getFields(): array`

With this you could write the `fields` method of your `HumanType` class like this:


```php
public function fields(): array
{
    $interface = GraphQL::type('Character');

    return [
        $interface->getField('id'),
        $interface->getField('name'),
        $interface->getField('appearsIn'),

        'totalCredits' => [
            'type' => Type::nonNull(Type::int()),
            'description' => 'The total amount of credits this human owns.'
        ]
    ];
}
```

Or by using the `getFields` method:

```php
public function fields(): array
{
    $interface = GraphQL::type('Character');

    return array_merge($interface->getFields(), [
        'totalCredits' => [
            'type' => Type::nonNull(Type::int()),
            'description' => 'The total amount of credits this human owns.'
        ]
    ]);
}
```

### Input Object

Input Object types allow you to create complex inputs. Fields have no args or resolve options and their type must be `InputType`. You can add rules option if you want to validate input data.
Read more about Input Object [here](https://graphql.org/learn/schema/#input-types)

First create an InputObjectType as an extension of the GraphQLType class:
```php
declare(strict_types = 1);
namespace App\GraphQL\InputObject;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\InputType;

class ReviewInput extends InputType
{
    protected $attributes = [
        'name' => 'reviewInput',
        'description' => 'A review with a comment and a score (0 to 5)'
    ];

    public function fields(): array
    {
        return [
            'comment' => [
                'name' => 'comment',
                'description' => 'A comment (250 max chars)',
                'type' => Type::string(),
                // You can define Laravel Validation here
                'rules' => ['max:250']
            ],
            'score' => [
                'name' => 'score',
                'description' => 'A score (0 to 5)',
                'type' => Type::int(),
                // You must use 'integer' on rules if you want to validate if the number is inside a range
                // Otherwise it will validate the number of 'characters' the number can have.
                'rules' => ['integer', 'min:0', 'max:5']
            ]
        ];
    }
}
```

The Input Object will be registered like any other type in your schema in `config/graphql.php`:

```php
'schemas' => [
    'default' => [
        'types' => [
            'ReviewInput' => ReviewInput::class
        ],
```

Then use it in a mutation, like:
```php
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class TestMutation extends Mutation
{
    public function args(): array
    {
        return [
            'review' => [
                'type' => GraphQL::type('ReviewInput')
            ]
        ];
    }
}
```

### OneOf Input Objects

OneOf Input Objects are a special type of input object where **exactly one field** must be provided. This is useful for creating polymorphic inputs or "input unions" where you want to accept one of several possible input types.
Read more about OneOf in the [RFC](https://github.com/graphql/graphql-spec/pull/825) or in the [GraphQL PHP Documentation](https://webonyx.github.io/graphql-php/type-definitions/inputs/#using-the-isoneof-configuration-option)

#### Creating a OneOf Input Type

Create a OneOf Input Type by setting `'isOneOf' => true` in the attributes:

```php
declare(strict_types = 1);
namespace App\GraphQL\InputObject;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\InputType;

class SearchInput extends InputType
{
    protected $attributes = [
        'name' => 'SearchInput',
        'description' => 'Search by exactly one criteria',
        'isOneOf' => true,
    ];

    public function fields(): array
    {
        return [
            'byId' => [
                'type' => Type::id(),
                'description' => 'Search by user ID',
            ],
            'byEmail' => [
                'type' => Type::string(),
                'description' => 'Search by email address',
            ],
            'byUsername' => [
                'type' => Type::string(),
                'description' => 'Search by username',
            ],
        ];
    }
}
```

#### Using OneOf Input Types
```php
declare(strict_types = 1);
namespace App\GraphQL\Queries;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;
use App\Models\User;

class UserQuery extends Query
{
    protected $attributes = [
        'name' => 'user',
        'description' => 'Find a user by search criteria'
    ];

    public function type(): Type
    {
        return GraphQL::type('User');
    }

    public function args(): array
    {
        return [
            'search' => [
                'type' => Type::nonNull(GraphQL::type('SearchInput')),
                'description' => 'Search criteria (exactly one field required)',
            ],
        ];
    }

    public function resolve($root, array $args)
    {
        $search = $args['search'];

        // Exactly one of these will be set
        if (isset($search['byId'])) {
            return User::find($search['byId']);
        }

        if (isset($search['byEmail'])) {
            return User::where('email', $search['byEmail'])->first();
        }

        if (isset($search['byUsername'])) {
            return User::where('username', $search['byUsername'])->first();
        }

        return null;
    }
}
```

#### Generating OneOf Input Types

You can generate a OneOf input type using the Artisan command with the `--oneof` flag:

```bash
php artisan make:graphql:input SearchInput --oneof
```

This will create a new input type with `'isOneOf' => true` already configured.

### Type modifiers

Type modifiers can be applied by wrapping your chosen type in `Type::nonNull` or `Type::listOf` calls
or alternatively you can use the shorthand syntax available via `GraphQL::type` to build up more complex
types.

```php
GraphQL::type('MyInput!');
GraphQL::type('[MyInput]');
GraphQL::type('[MyInput]!');
GraphQL::type('[MyInput!]!');

GraphQL::type('String!');
GraphQL::type('[String]');
GraphQL::type('[String]!');
GraphQL::type('[String!]!');
```

### Field and input alias

It is possible to alias query and mutation arguments as well as input object fields.

It can be especially useful for mutations saving data to the database.

Here you might want the input names to be different from the column names in the database.

Example, where the database columns are `first_name` and `last_name`:

```php
declare(strict_types = 1);
namespace App\GraphQL\InputObject;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\InputType;

class UserInput extends InputType
{
    protected $attributes = [
        'name' => 'userInput',
        'description' => 'A user.'
    ];

    public function fields(): array
    {
        return [
            'firstName' => [
                'alias' => 'first_name',
                'description' => 'The first name of the user',
                'type' => Type::string(),
                'rules' => ['max:30']
            ],
            'lastName' => [
                'alias' => 'last_name',
                'description' => 'The last name of the user',
                'type' => Type::string(),
                'rules' => ['max:30']
            ]
        ];
    }
}
```

```php
declare(strict_types = 1);
namespace App\GraphQL\Mutations;

use Closure;
use App\Models\User;
use Rebing\GraphQL\Support\Facades\GraphQL;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Mutation;

class UpdateUserMutation extends Mutation
{
    protected $attributes = [
        'name' => 'updateUser'
    ];

    public function type(): Type
    {
        return GraphQL::type('User');
    }

    public function args(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::string())
            ],
            'input' => [
                'type' => GraphQL::type('UserInput')
            ]
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $user = User::find($args['id']);
        $user->fill($args['input']);
        $user->save();

        return $user;
    }
}
```

### JSON columns

When using JSON columns in your database, the field won't be defined as a "relationship",
but rather a simple column with nested data. To get a nested object that's not a database relationship,
use the `is_relation` attribute in your Type:

```php
class UserType extends GraphQLType
{
    // ...

    public function fields(): array
    {
        return [
            // ...

            // JSON column containing all posts made by this user
            'posts' => [
                'type'          => Type::listOf(GraphQL::type('Post')),
                'description'   => 'A list of posts written by the user',
                // Now this will simply request the "posts" column, and it won't
                // query for all the underlying columns in the "post" object
                // The value defaults to true
                'is_relation' => false
            ]
        ];
    }

    // ...
}
```

### Field deprecation

Sometimes you would want to deprecate a field but still have to maintain backward compatibility
until clients completely stop using that field. You can deprecate a field using
[directive](https://www.graphql-tools.com/docs/generate-schema/#descriptions--deprecations). If you add `deprecationReason`
to field attributes it will become marked as deprecated in GraphQL documentation. You can validate schema on client
using [Apollo GraphOS](https://www.apollographql.com/docs/graphos/schema-design/schema-checks/).


```php
declare(strict_types = 1);
namespace App\GraphQL\Types;

use App\Models\User;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class UserType extends GraphQLType
{
    protected $attributes = [
        'name'          => 'User',
        'description'   => 'A user',
        'model'         => User::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The id of the user',
            ],
            'email' => [
                'type' => Type::string(),
                'description' => 'The email of user',
            ],
            'address' => [
                'type' => Type::string(),
                'description' => 'The address of user',
                'deprecationReason' => 'Deprecated due to address field split'
            ],
            'address_line_1' => [
                'type' => Type::string(),
                'description' => 'The address line 1 of user',
            ],
            'address_line_2' => [
                'type' => Type::string(),
                'description' => 'The address line 2 of user',
            ],
        ];
    }
}
```

### Default field resolver

It's possible to override the default field resolver provided by the underlying
webonyx/graphql-php library using the config option `defaultFieldResolver`.

You can define any valid callable (static class method, closure, etc.) for it:

```php
'defaultFieldResolver' => [Your\Klass::class, 'staticMethod'],
```

The parameters received are your regular "resolve" function signature.

### Macros

If you would like to define some helpers that you can re-use in a variety of your
queries, mutations and types, you may use the macro method on the `GraphQL` facade.

For example, from a service provider's boot method:

```php
declare(strict_types = 1);
namespace App\Providers;

use GraphQL\Type\Definition\Type;
use Illuminate\Support\ServiceProvider;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        GraphQL::macro('listOf', function (string $name): Type {
            return Type::listOf(GraphQL::type($name));
        });
    }
}
```

The `macro` function accepts a name as its first argument, and a `Closure` as its second.

### Automatic Persisted Queries support

Automatic Persisted Queries (APQ) improve network performance by sending smaller requests, with zero build-time configuration.

APQ is disabled by default and can be enabled in the config via `apq.enable=true` or by setting the environment variable `GRAPHQL_APQ_ENABLE=true`.

A persisted query is an ID or hash that can be generated on the client sent to the server instead of the entire GraphQL query string. 
This smaller signature reduces bandwidth utilization and speeds up client loading times.
Persisted queries pair especially with GET requests, enabling the browser cache and integration with a CDN.
Note that GET requests are disabled by default; to use APQ with GET, you must explicitly set `'method' => ['GET', 'POST']` in your schema configuration.

Behind the scenes, APQ uses Laravel's cache for storing / retrieving the queries.
They are parsed by GraphQL before storing, so re-parsing them again is not necessary.
Please see the various options there for which cache, prefix, TTL, etc. to use.

> Note: it is advised to clear the cache after a deployment to accommodate for changes in your schema!

For more information see: 
 - [Apollo - Automatic persisted queries](https://www.apollographql.com/docs/apollo-server/performance/apq/) 
 - [Apollo Client - Persisted Query Link](https://www.apollographql.com/docs/react/api/link/persisted-queries/)

> Note: the APQ protocol requires the hash sent by the client being compared
> with the computed hash on the server. In case a mutating middleware like
> `TrimStrings` is active and the query sent contains leading/trailing
> whitespaces, these hashes can never match resulting in an error.
> 
> In such case either disable the middleware or trim the query on the client
> before hashing.

#### Notes
 - The error descriptions are aligned with [apollo-server](https://github.com/apollographql/apollo-server).

#### Client example

Below a simple integration example with Vue 3 and Apollo Client, where `createPersistedQueryLink`
automatically manages the APQ flow.

```js
// [example apollo.js]

import { ApolloClient, InMemoryCache, HttpLink, ApolloLink } from '@apollo/client/core';
import { createPersistedQueryLink } from '@apollo/client/link/persisted-queries';
import { sha256 } from 'crypto-hash';

const httpLink = new HttpLink({ uri: '/graphql' });
const persistedQueryLink = createPersistedQueryLink({ sha256 });

export const apolloClient = new ApolloClient({
  link: ApolloLink.from([persistedQueryLink, httpLink]),
  cache: new InMemoryCache(),
  connectToDevTools: true,
});
```
```vue
<!-- [example TestComponent.vue] -->

<template>
  <div>
    <p>Test APQ</p>
    <p v-if="loading">Loading...</p>
    <p v-else>{{ result?.hello }}</p>
  </div>
</template>

<script setup>
import { useQuery } from '@vue/apollo-composable';
import gql from 'graphql-tag';

const { result, loading } = useQuery(gql`
  query {
    hello
  }
`);
</script>
```

### Tracing / Observability

GraphQL operations can be instrumented with timing data by configuring a tracing
driver. Tracing is **disabled by default** (`'driver' => null`).

The built-in `OpenTelemetryTracingDriver` emits spans via the OpenTelemetry API
following the [GraphQL semantic conventions](https://opentelemetry.io/docs/specs/semconv/graphql/graphql-spans/).
It requires the `open-telemetry/api` ^1.0 package.

#### Enabling OpenTelemetry

Install the OpenTelemetry API package first:

```bash
composer require open-telemetry/api
```

Then configure the driver:

```php
'tracing' => [
    'driver' => \Rebing\GraphQL\Support\Tracing\OpenTelemetryTracingDriver::class,
    'driver_options' => [
        // Include the GraphQL document in spans (may contain sensitive data)
        'include_document' => true,
    ],
],
```

Without an OTel SDK configured, all spans are automatically no-ops.

To actually collect and export spans, you need to install and configure the
[OpenTelemetry PHP SDK](https://opentelemetry.io/docs/languages/php/sdk/) along
with an [exporter](https://opentelemetry.io/docs/languages/php/exporters/) for
your backend. The
[Getting Started guide](https://opentelemetry.io/docs/languages/php/getting-started/)
walks through a complete example. Once the SDK is configured, the driver
automatically picks up the global `TracerProvider` - no additional wiring is
needed in this package.

#### Per-field resolver tracing

By default, only the top-level operation is traced. To instrument individual
field resolvers, enable `field_tracing`:

```php
'tracing' => [
    'driver' => \Rebing\GraphQL\Support\Tracing\OpenTelemetryTracingDriver::class,
    'field_tracing' => true,
],
```

With OpenTelemetry this creates a child span for each resolved field.

When tracing is enabled (i.e. a `driver` is configured), the tracing execution
and resolver middlewares (`TracingExecutionMiddleware` and
`TracingResolverMiddleware`) are automatically registered - you do not need to
add them to the `execution_middleware` or `resolver_middleware_append` config
arrays manually.

> **Note:** Field tracing produces high-cardinality data and is intended for
> development/debugging. Use it with caution in production.

#### Custom tracing drivers

You can implement the `Rebing\GraphQL\Support\Tracing\TracingDriver` interface
to create your own driver. The interface has four methods:

- `startOperation(schemaName, operationName, operationType, source)` - called before execution
- `endOperation(context, ExecutionResult)` - called after execution; receives the opaque context from `startOperation` and may modify the result
- `startFieldResolve(ResolveInfo)` - called before each field resolve (when field tracing is enabled)
- `endFieldResolve(context, ResolveInfo)` - called after each field resolve

Register your driver class in the `tracing.driver` config key and it will be
resolved from the Laravel service container. If the driver constructor accepts
an `array $driverOptions` parameter, it will receive the merged `driver_options`
from the global and per-schema tracing config.

#### Per-schema tracing

By default, the global `tracing` configuration applies to every schema. You can
override tracing on a per-schema basis by adding a `tracing` key inside the
schema's config array.

**Disable tracing for a specific schema:**

```php
'schemas' => [
    'internal' => [
        'query' => [/* ... */],
        'tracing' => false, // no tracing for this schema
    ],
],
```

**Enable tracing only for a specific schema** (no global driver):

```php
// Global: tracing disabled
'tracing' => [
    'driver' => null,
],

'schemas' => [
    'default' => [
        'query' => [/* ... */],
        // No 'tracing' key - inherits global (disabled)
    ],
    'monitored' => [
        'query' => [/* ... */],
        'tracing' => [
            'driver' => \Rebing\GraphQL\Support\Tracing\OpenTelemetryTracingDriver::class,
            'field_tracing' => true,
        ],
    ],
],
```

**Override driver options per schema** (deep-merged over global):

```php
// Global: tracing enabled, document excluded
'tracing' => [
    'driver' => \Rebing\GraphQL\Support\Tracing\OpenTelemetryTracingDriver::class,
    'driver_options' => [
        'include_document' => false,
    ],
],

'schemas' => [
    'debug' => [
        'query' => [/* ... */],
        'tracing' => [
            'field_tracing' => true,
            'driver_options' => [
                'include_document' => true, // override for this schema only
            ],
        ],
    ],
],
```

Per-schema `tracing` arrays are deep-merged over the global config: schema
values win for top-level keys, and `driver_options` is merged separately so you
can override individual options without repeating the full array.

## Security

GraphQL APIs have a different attack surface than REST APIs. A single endpoint
accepts arbitrary queries, so without safeguards a client can craft deeply
nested or highly complex queries that exhaust server resources.

### Introspection

Schema introspection lets clients discover your entire type system -- every
query, mutation, field, and argument. This is essential for development tooling
(GraphiQL, IDE plugins, codegen) but exposes your full API surface in
production.

Introspection is **disabled by default**:

```php
// config/graphql.php
'security' => [
    'disable_introspection' => env('GRAPHQL_DISABLE_INTROSPECTION', true),
],
```

Set `GRAPHQL_DISABLE_INTROSPECTION=false` in your `.env` during development.

### Query depth limiting

Deeply nested queries can cause excessive resolver calls and memory usage. The
`query_max_depth` option rejects queries that exceed the allowed nesting level:

```php
'security' => [
    'query_max_depth' => 13, // default
],
```

For example, with a depth limit of 3, the query `{ users { posts { comments { author { name } } } } }` would be rejected because it nests 4 levels deep.

Tune this based on your schema's legitimate nesting requirements. Start strict
and increase only if real queries require it.

### Query complexity analysis

Complex queries (many fields, large lists) can be expensive even when shallow.
The `query_max_complexity` option assigns a cost to each resolved field and
rejects queries that exceed the budget:

```php
'security' => [
    'query_max_complexity' => 500, // default
],
```

You can assign custom complexity to individual fields using the `complexity`
callback supported by webonyx/graphql-php:

```php
'posts' => [
    'type' => Type::listOf(GraphQL::type('Post')),
    'complexity' => fn (int $childCost, array $args): int => $childCost * ($args['limit'] ?? 10),
],
```

See the [webonyx/graphql-php security documentation](https://webonyx.github.io/graphql-php/security/)
for full details on how complexity is calculated.

### Batching limits

When [batching](#batching) is enabled, clients can send multiple operations in a
single HTTP request. Without a cap, this can be used to amplify the impact of
expensive queries. Batching is disabled by default, and when enabled the
`batching.max_batch_size` option (default: `10`) limits the number of operations
per request.

### Recommended production configuration

```php
// config/graphql.php
'security' => [
    'disable_introspection' => env('GRAPHQL_DISABLE_INTROSPECTION', true),
    'query_max_depth' => 13,
    'query_max_complexity' => 500,
],

'batching' => [
    'enable' => false,
],
```

Additional measures to consider at the infrastructure level:
- **Rate limiting** -- Apply Laravel's `ThrottleRequests` middleware via
  `route.middleware` or per-schema `middleware` to limit requests per client.
- **Request size limits** -- Configure your web server (Nginx `client_max_body_size`,
  Apache `LimitRequestBody`) to reject oversized request bodies.
- **Timeout limits** -- Set PHP `max_execution_time` and web server timeouts to
  prevent long-running queries from holding connections open.
- **Persisted queries** -- Enable [APQ](#automatic-persisted-queries-support) and,
  once warmed, consider rejecting ad-hoc queries entirely for maximum lockdown.

## Error handling

This library has two distinct error layers:

- **Errors** (`Rebing\GraphQL\Error\*`): Extend `GraphQL\Error\Error` from
  webonyx/graphql-php. These are **client-safe** and appear in GraphQL JSON
  responses.
- **Exceptions** (`Rebing\GraphQL\Exception\*`): Extend `RuntimeException`.
  These indicate **configuration or developer errors** (e.g. a missing schema or
  unregistered type) and are not included in GraphQL responses.

### Built-in error types

| Class | Category | When thrown |
|-------|----------|------------|
| `ValidationError` | `validation` | Argument validation rules fail (via `rules()` or inline `'rules'` key) |
| `AuthorizationError` | `authorization` | `authorize()` returns anything other than `true` |
| `AutomaticPersistedQueriesError` | `apq` | APQ hash mismatch, query not found, or APQ disabled |

### Error response format

Errors are returned in the standard GraphQL `errors` array. The library enriches
each error with an `extensions` key:

```json
{
  "errors": [
    {
      "message": "validation",
      "extensions": {
        "category": "validation",
        "validation": {
          "email": ["The email field is required."]
        }
      },
      "locations": [{"line": 1, "column": 20}]
    }
  ]
}
```

For `AuthorizationError`, the response contains `extensions.category` set to
`"authorization"` and the `message` from `getAuthorizationMessage()` (defaults
to `"Unauthorized"`).

### Error reporting

The default `errors_handler` selectively reports errors to Laravel's exception
handler:

- `ValidationError` and `AuthorizationError` are **not reported** (they are
  expected application-level errors, not bugs).
- GraphQL syntax/type errors (e.g. invalid queries) are **not reported**.
- All other exceptions (unexpected errors, database failures, etc.) **are
  reported** through Laravel's `ExceptionHandler`, which typically logs them.

### Customizing error formatting

You can replace the default error formatter and/or error handler via config:

```php
// config/graphql.php

// Receives each GraphQL\Error\Error; must return an array
'error_formatter' => [App\GraphQL\ErrorFormatter::class, 'format'],

// Receives all errors + the formatter; must return an array of formatted errors
'errors_handler' => [App\GraphQL\ErrorHandler::class, 'handle'],
```

The default formatter (`GraphQL::formatError`) respects `app.debug`: when debug
mode is enabled, errors include `debugMessage` and `trace` fields for easier
development. In production these are omitted.

> **Tip:** Laravel's built-in `ValidationException` (thrown by `Validator::validate()`)
> is also handled by the default formatter -- it is automatically converted to the
> same `extensions.validation` format shown above.

## Misc features

### Detecting unused variables

By default, `'variables'` provided alongside the GraphQL query which are **not**
consumed, are silently ignored.

If you consider the hypothetical case you have an optional (nullable) argument
in your query, and you provide a variable argument for it but you make a typo,
this can go unnoticed.

Example:
```graphql
mutation test($value:ID) {
  someMutation(type:"falbala", optional_id: $value)
}
```
Variables provided:
```json5
{
  // Ops! typo in `values`
  "values": "138"
}
```

In this case, nothing happens and `optional_id` will be treated as not being provided.

To prevent such scenarios, you can add the `UnusedVariablesMiddleware` to your
`execution_middleware`.

## Configuration options

| Option | Default | Description |
|--------|---------|-------------|
| `route.prefix` | `graphql` | URL prefix for GraphQL endpoints (without leading `/`) |
| `route.controller` | Built-in | Override the default controller class (supports string and array format) |
| `route.middleware` | `[]` | Global HTTP middleware for all schemas (unless overridden per-schema) |
| `route.group_attributes` | `[]` | Additional route group attributes |
| `default_schema` | `'default'` | Name of the default schema when none is specified via the route |
| `batching.enable` | `false` | Enable/disable GraphQL [batching](#batching) |
| `batching.max_batch_size` | `10` | Max operations per batch (`null` for no limit) |
| `error_formatter` | Built-in | Callable receiving each Error object; must return an array |
| `errors_handler` | Built-in | Custom error handling; default passes exceptions to Laravel's error handler |
| `security.query_max_complexity` | `500` | Maximum allowed query complexity. See [graphql-php security docs](https://webonyx.github.io/graphql-php/security/) |
| `security.query_max_depth` | `13` | Maximum allowed query depth |
| `security.disable_introspection` | `true` | Disable schema introspection (env: `GRAPHQL_DISABLE_INTROSPECTION`) |
| `pagination_type` | Built-in | Custom pagination type class |
| `simple_pagination_type` | Built-in | Custom simple pagination type class |
| `cursor_pagination_type` | Built-in | Custom cursor pagination type class |
| `defaultFieldResolver` | `null` | Override the [default field resolver](https://webonyx.github.io/graphql-php/data-fetching/#default-field-resolver) |
| `headers` | `[]` | Headers added to responses from the default controller |
| `json_encoding_options` | `0` | JSON encoding options for responses from the default controller |
| `apq.enable` | `false` | Enable [Automatic Persisted Queries](#automatic-persisted-queries-support) |
| `apq.cache_driver` | App default | Cache driver for APQ (defaults to your app's `cache.default` driver; env: `GRAPHQL_APQ_CACHE_DRIVER`) |
| `apq.cache_prefix` | `'{cache.prefix}:graphql.apq'` | Cache key prefix for persisted queries |
| `apq.cache_ttl` | `300` | Cache TTL in seconds for persisted queries |
| `schemas` | | Defines available schemas and their settings. See [Schemas](#schemas) |
| `schemas.*.query` | `[]` | Array of query classes for this schema |
| `schemas.*.mutation` | `[]` | Array of mutation classes for this schema |
| `schemas.*.types` | `[]` | Array of type classes scoped to this schema |
| `schemas.*.middleware` | — | Per-schema HTTP middleware (overrides `route.middleware`) |
| `schemas.*.method` | `['POST']` | HTTP methods to support (must be uppercase) |
| `schemas.*.execution_middleware` | — | Per-schema execution middleware (overrides global `execution_middleware`) |
| `schemas.*.route_attributes` | `[]` | Additional Laravel route attributes (e.g. `domain`, `prefix`) |
| `schemas.*.controller` | — | Override the controller for this schema |
| `schemas.*.tracing` | — | Per-schema tracing overrides |
| `types` | `[]` | Global types shared across all schemas. See [Creating a query](#creating-a-query) |
| `execution_middleware` | Built-in set | Global [execution middleware](#graphql-execution-middleware) classes. Terminal middleware is always appended automatically |
| `resolver_middleware_append` | `null` | Global [resolver middleware](#resolver-middleware) appended after per-field middleware |
| `tracing.driver` | `null` | Tracing driver class (`null` = disabled). Built-in: `OpenTelemetryTracingDriver`. See [Tracing](#tracing--observability) |
| `tracing.field_tracing` | `false` | Instrument individual field resolvers |
| `tracing.driver_options` | `[]` | Array of options passed to the driver constructor (e.g. `'include_document' => true`) |

## Performance considerations

### Wrap Types

You can wrap types to add more information to the queries and mutations. Similar to how pagination works, you can do the same with your extra data that you want to inject. For instance, in your query:

```php
public function type(): Type
{
    return GraphQL::wrapType(
        'PostType',
        'PostMessageType',
        \App\GraphQL\Types\WrapMessagesType::class,
    );
}

public function resolve($root, array $args)
{
    return [
        'data' => Post::find($args['post_id']),
        'messages' => new Collection([
                new SimpleMessage("Congratulations, the post was found"),
                new SimpleMessage("This post cannot be edited", "warning"),
        ]),
    ];
}
```

#### Using wrap types with `SelectFields`

If you use `SelectFields` (via the `$getSelectFields` closure) in a query that
returns a wrap type, your wrapper class **must** implement the
`Rebing\GraphQL\Support\Contracts\WrapType` marker interface. This tells
`SelectFields` to look through the wrapper's `data` field to find the underlying
model type and generate the correct `SELECT`/`WITH` clauses.

```php
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Contracts\WrapType;
use Rebing\GraphQL\Support\Facades\GraphQL;

class PostWrappedType extends ObjectType implements WrapType
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'PostWrapped',
            'fields' => fn () => [
                'data' => [
                    'type' => Type::listOf(GraphQL::type('Post')),
                    'is_relation' => false,
                ],
                'message' => [
                    'type' => Type::string(),
                    'selectable' => false,
                ],
            ],
        ]);
    }
}
```

The built-in pagination types (`PaginationType`, `SimplePaginationType`,
`CursorPaginationType`) already implement this interface. Custom pagination
classes configured via the `pagination_type`, `simple_pagination_type`, or
`cursor_pagination_type` config keys must also implement it.

## Known limitations

### SelectFields related
- Resolving fields via aliases will only resolve them once, even if the fields
  have different arguments ([Issue](https://github.com/rebing/graphql-laravel/issues/604)).

## GraphQL testing clients
 - [Firecamp](https://firecamp.io/graphql)
 - [GraphiQL](https://github.com/graphql/graphiql) [integration via laravel-graphiql](https://github.com/mll-lab/laravel-graphiql)

## Testing

You can test your GraphQL API using Laravel's built-in HTTP testing helpers. No
additional packages are required.

### Querying an endpoint

Use `postJson` to send a GraphQL request and assert the response:

```php
namespace Tests\Feature;

use Tests\TestCase;

class BooksQueryTest extends TestCase
{
    public function test_can_query_books(): void
    {
        $response = $this->postJson('/graphql', [
            'query' => '{ books { id title author } }',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'books' => [
                        '*' => ['id', 'title', 'author'],
                    ],
                ],
            ]);
    }
}
```

### Using query variables

```php
public function test_can_fetch_user_by_id(): void
{
    $response = $this->postJson('/graphql', [
        'query' => 'query FetchUser($id: String!) { user(id: $id) { id email } }',
        'variables' => ['id' => '1'],
    ]);

    $response->assertOk()
        ->assertJsonPath('data.user.id', '1');
}
```

### Testing mutations

```php
public function test_can_update_user_password(): void
{
    $response = $this->postJson('/graphql', [
        'query' => 'mutation { updateUserPassword(id: "1", password: "newpassword") { id } }',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.updateUserPassword.id', '1');
}
```

### Testing a non-default schema

Pass the schema name as part of the URL path:

```php
public function test_user_schema_requires_auth(): void
{
    $response = $this->postJson('/graphql/user', [
        'query' => '{ profile { id email } }',
    ]);

    $response->assertUnauthorized();
}
```

### Asserting errors

```php
public function test_authorization_rejects_guest(): void
{
    $response = $this->postJson('/graphql', [
        'query' => '{ protectedQuery { id } }',
    ]);

    $response->assertOk()
        ->assertJsonPath('errors.0.message', 'Unauthorized');
}

public function test_validation_returns_errors(): void
{
    $response = $this->postJson('/graphql', [
        'query' => 'mutation { updateUserEmail(id: "", email: "not-an-email") { id } }',
    ]);

    $response->assertOk()
        ->assertJsonPath('errors.0.message', 'validation')
        ->assertJsonStructure([
            'errors' => [
                ['extensions' => ['validation']],
            ],
        ]);
}
```

> **Tip:** For database-backed tests, use Laravel's `RefreshDatabase` or
> `DatabaseTransactions` trait as you would in any feature test.

## Upgrading

For upgrade guides, see [UPGRADE.md](UPGRADE.md):

- [Upgrading from 9 to 10](UPGRADE.md#upgrading-from-9-to-10)
- [Upgrading from v1 to v2](UPGRADE.md#upgrading-from-v1-to-v2)
