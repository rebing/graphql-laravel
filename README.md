# Laravel GraphQL

[![Latest Stable Version](https://poser.pugx.org/rebing/graphql-laravel/v/stable)](https://packagist.org/packages/rebing/graphql-laravel)
[![License](https://poser.pugx.org/rebing/graphql-laravel/license)](https://packagist.org/packages/rebing/graphql-laravel)
[![Tests](https://github.com/rebing/graphql-laravel/workflows/Tests/badge.svg)](https://github.com/rebing/graphql-laravel/actions?query=workflow%3ATests)
[![Downloads](https://img.shields.io/packagist/dt/rebing/graphql-laravel.svg?style=flat-square)](https://packagist.org/packages/rebing/graphql-laravel)
[![Get on Slack](https://img.shields.io/badge/slack-join-orange.svg)](https://join.slack.com/t/rebing-graphql/shared_invite/enQtNTE5NjQzNDI5MzQ4LTdhNjk0ZGY1N2U1YjE4MGVlYmM2YTc2YjQ0MmIwODY5MWMwZWIwYmY1MWY4NTZjY2Q5MzdmM2Q3NTEyNDYzZjc)

Use Facebook's GraphQL with PHP 7.4+ on Laravel 6.0 & 8.0+. It is based on the [PHP port of GraphQL reference implementation](https://github.com/webonyx/graphql-php). You can find more information about GraphQL in the [GraphQL Introduction](https://reactjs.org/blog/2015/05/01/graphql-introduction.html) on the [React](https://reactjs.org/) blog or you can read the [GraphQL specifications](https://spec.graphql.org/).

* Allows creating **queries** and **mutations** as request endpoints
* Supports multiple schemas
  * per schema queries/mutations/types 
  * per schema HTTP middlewares
  * per schema GraphQL execution middlewares
* Custom GraphQL **resolver middleware** can be defined for each query/mutation
  
When using the `SelectFields` class for Eloquent support, additional features are available:
* Queries return **types**, which can have custom **privacy** settings.
* The queried fields will have the option to be retrieved **dynamically** from the database.

It offers following features and improvements over the original package by
[Folklore](https://github.com/folkloreinc/laravel-graphql):
* Per-operation authorization
* Per-field callback defining its visibility (e.g. hiding from unauthenticated users)
* `SelectFields` abstraction available in `resolve()`, allowing for advanced eager loading
  and thus dealing with n+1 problems
* Pagination support
* Server-side support for [query batching](https://www.apollographql.com/blog/batching-client-graphql-queries-a685f5bcd41b/)
* Support for file uploads

## Installation

### Dependencies:

* [Laravel 6.0+](https://github.com/laravel/laravel)
* [GraphQL PHP](https://github.com/webonyx/graphql-php)


### Installation:

Require the package via Composer:
```bash
composer require rebing/graphql-laravel
```

#### Laravel

Publish the configuration file:
```bash
php artisan vendor:publish --provider="Rebing\GraphQL\GraphQLServiceProvider"
```

Review the configuration file:
```
config/graphql.php
```

The default GraphiQL view makes use of the global `csrf_token()` helper function.

## Usage

- [Laravel GraphQL](#laravel-graphql)
  - [Installation](#installation)
    - [Dependencies:](#dependencies)
    - [Installation:](#installation-1)
      - [Laravel](#laravel)
  - [Usage](#usage)
    - [Concepts](#concepts)
      - [A word on declaring a field `nonNull`](#a-word-on-declaring-a-field-nonnull)
    - [Data loading](#data-loading)
    - [GraphiQL](#graphiql)
    - [Middleware Overview](#middleware-overview)
      - [HTTP middleware](#http-middleware)
      - [GraphQL execution middleware](#graphql-execution-middleware)
      - [GraphQL resolver middleware](#graphql-resolver-middleware)
    - [Schemas](#schemas)
      - [Schema classes](#schema-classes)
    - [Creating a query](#creating-a-query)
    - [Creating a mutation](#creating-a-mutation)
      - [File uploads](#file-uploads)
        - [Vue.js and Axios example](#vuejs-and-axios-example)
        - [jQuery or vanilla javascript](#jquery-or-vanilla-javascript)
    - [Validation](#validation)
      - [Example defining rules in each argument](#example-defining-rules-in-each-argument)
      - [Example using the `rules()` method](#example-using-the-rules-method)
      - [Example using Laravel's validator directly](#example-using-laravels-validator-directly)
      - [Handling validation errors](#handling-validation-errors)
      - [Customizing error messages](#customizing-error-messages)
      - [Customizing attributes](#customizing-attributes)
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
    - [Unions](#unions)
    - [Interfaces](#interfaces)
      - [Supporting custom queries on interface relations](#supporting-custom-queries-on-interface-relations)
      - [Sharing interface fields](#sharing-interface-fields)
    - [Input Object](#input-object)
    - [Type modifiers](#type-modifiers)
    - [Field and input alias](#field-and-input-alias)
    - [JSON columns](#json-columns)
    - [Field deprecation](#field-deprecation)
    - [Default field resolver](#default-field-resolver)
    - [Macros](#macros)
    - [Automatic Persisted Queries support](#automatic-persisted-queries-support)
      - [Notes](#notes)
      - [Client example](#client-example)
  - [Misc features](#misc-features)
    - [Detecting unused variables](#detecting-unused-variables)
  - [Configuration options](#configuration-options)
  - [Guides](#guides)
    - [Upgrading from v1 to v2](#upgrading-from-v1-to-v2)
    - [Migrating from Folklore](#migrating-from-folklore)
  - [Performance considerations](#performance-considerations)
    - [Lazy loading of types](#lazy-loading-of-types)
      - [Example of aliasing **not** supported by lazy loading](#example-of-aliasing-not-supported-by-lazy-loading)
    - [Wrap Types](#wrap-types)
  - [GraphQL testing clients](#graphql-testing-clients)

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
[avoid n+1 problems](https://www.google.com/search?hl=en&q=n%2B1%20problem).

Be aware that this is not the only way and it's also common to use _concepts_
called "dataloaders". They usually take advantage of "deferred" executions of
resolved fields, as explained in [graphql-php solving n+1 problem](https://github.com/webonyx/graphql-php/blob/master/docs/data-fetching.md#solving-n1-problem).

The gist is that you can use any kind of data source you like (Eloquent,
static data, ElasticSearch results, caching, etc.) in your resolvers but you've
to be mindful of the execution model to avoid repetitive fetches and perform
smart pre-fetching of your data.

### GraphiQL

GraphiQL is lightweight "GraphQL IDE" in your browser. It takes advantage of the
GraphQL type system and allows autocompletion of all queries/mutations/types and
fields.

GraphiQL in the meantime evolved in terms of features and complexity, thus for
convenience an older version is directly included with this library.

As enabled by the default configuration, it's available under the `/graphiql`
route.

If you are using multiple schemas, you can access them via `/graphiql/<schema name>`.

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
'schema' => 'default',

'schemas' => [
    'default' => [
        'query' => [
            ExampleQuery::class,
            // It's possible to specify a name/alias with the key
            // but this is discouraged as it prevents things
            // like improving performance with e.g. `lazyload_types=true`
            // It's recommended to specify just the class here and
            // rely on the `'name'` attribute in the query / type.
            'someQuery' => AnotherExampleQuery::class,
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
        'method' => ['GET', 'POST'], 
        'execution_middleware' => [
            \Rebing\GraphQL\Support\ExecutionMiddleware\UnusedVariablesMiddleware::class,
        ],
    ],
],
```

Together with the configuration, in a way the schema defines also the route by
which it is accessible. Per the default configuration of `prefix = graphql`, the
_default_ schema is accessible via `/graphql`.




#### Schema classes

You may alternatively define the configuration of a schema in a class that implements `ConfigConvertible`.

In your config, you can reference the name of the class, rather than an array.

```php
'schemas' => [
    'default' => DefaultSchema::class
]
```

```php
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

> **Note:** The `selectable` key is required, if it's a non-database field or not a relation

```php
namespace App\GraphQL\Types;

use App\User;
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

As with queries/mutations, you can use an alias name (though again this prevents
it from taking advantage of lazy type loading):
```php
'schemas' => [
    'default' => [
        // ...
        
        'types' => [
            'Useralias' => App\GraphQL\Types\UserType::class,
        ],
```

Then you need to define a query that returns this type (or a list). You can also specify arguments that you can use in the resolve method.
```php
namespace App\GraphQL\Queries;

use Closure;
use App\User;
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

And that's it. You should be able to query GraphQL with a request to the url `/graphql` (or anything you choose in your config). Try a GET request with the following `query` input

```graphql
query FetchUsers {
    users {
        id
        email
    }
}
```

For example, if you use homestead:
```
http://homestead.app/graphql?query=query+FetchUsers{users{id,email}}
```

### Creating a mutation

A mutation is like any other query. It accepts arguments and returns an object of a certain type. Mutations are meant to be used for operations **modifying** (mutating) the state on the server (which queries are not supposed to perform).

This is conventional abstraction, technically you can do anything you want in a query resolve, including mutating state.

For example, a mutation to update the password of a user. First you need to define the Mutation:

```php
namespace App\GraphQL\Mutations;

use Closure;
use App\User;
use GraphQL;
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

        $user->password = bcrypt($args['password']);
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

if you use homestead:
```
http://homestead.app/graphql?query=mutation+users{updateUserPassword(id: "1", password: "newpassword"){id,email}}
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
namespace App\GraphQL\Mutations;

use Closure;
use GraphQL;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
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

Note: You can test your file upload implementation using [Altair](https://altair.sirmuel.design/) as explained [here](https://www.xkoji.dev/blog/working-with-file-uploads-using-altair-graphql/).

##### Vue.js and Axios example

```vue
<template>
  <div class="input-group">
    <div class="custom-file">
      <input type="file" class="custom-file-input" id="uploadFile" ref="uploadFile" @change="handleUploadChange">
      <label class="custom-file-label" for="uploadFile">
        Drop Files Here to upload
      </label>
    </div>
    <div class="input-group-append">
      <button class="btn btn-outline-success" type="button" @click="upload">Upload</button>
    </div>
  </div>
</template>

<script>
  export default {
    name: 'FileUploadExample',
    data() {
      return {
        file: null,
      };
    },
    methods: {
      handleUploadChange() {
        this.file = this.$refs.uploadFile.files[0];
      },
      async upload() {
        if (!this.file) {
          return;
        }
        // Creating form data object
        let bodyFormData = new FormData();
        bodyFormData.set('operations', JSON.stringify({
                   // Mutation string
            'query': `mutation uploadSingleFile($file: Upload!) {
                        upload_single_file  (attachment: $file)
                      }`,
            'variables': {"attachment": this.file}
        }));
        bodyFormData.set('operationName', null);
        bodyFormData.set('map', JSON.stringify({"file":["variables.file"]}));
        bodyFormData.append('file', this.file);

        // Post the request to GraphQL controller
        let res = await axios.post('/graphql', bodyFormData, {
          headers: {
            "Content-Type": "multipart/form-data"
          }
        });

        if (res.data.status.code == 200) {
          // On success file upload
          this.file = null;
        }
      }
    }
  }
</script>

<style scoped>
</style>
```

##### jQuery or vanilla javascript
```html
<input type="file" id="fileUpload">
```
```javascript
// Get the file from input element
// In jQuery:
let file = $('#fileUpload').prop('files')[0];
// Vanilla JS:
let file = document.getElementById("fileUpload").files[0];

// Create a FormData object
let bodyFormData = new FormData();
bodyFormData.set('operations', JSON.stringify({
         // Mutation string
  'query': `mutation uploadSingleFile($file: Upload!) {
              upload_single_file  (attachment: $file)
            }`,
  'variables': {"attachment": this.file}
}));
bodyFormData.set('operationName', null);
bodyFormData.set('map', JSON.stringify({"file":["variables.file"]}));
bodyFormData.append('file', this.file);

// Post the request to GraphQL controller via Axios, jQuery.ajax, or vanilla XMLHttpRequest
let res = await axios.post('/graphql', bodyFormData, {
  headers: {
    "Content-Type": "multipart/form-data"
  }
});
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
namespace App\GraphQL\Mutations;

use Closure;
use App\User;
use GraphQL;
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
- `'rules' => 'required|string`\
  or
- `'rules' => ['required', 'string']`\
  or
- `'rules' => function (…) { … }`\
  etc.

For the `args()` method or the `'args'` definition for a field, the field names
are directly used for the validation. However, for input types, which can be
nested and occur multiple times, the field names are mapped as e.g.
`data.0.fieldname`. This is imported to understand when returning rules from
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

#### Misc notes

Certain type declarations of GraphQL may cancel our or render certain validations
unnecessary. A good example is using `Type::nonNull()` to ultimately declare
that an argument is required. In such a case a `'rules' => 'required'`
configuration will likely never be triggered, because the GraphQL execution
engine already prevents this field from being accepted in the first place.

Or to be more clear: if a GraphQL type system violation occurs, then no Laravel
validation will be even execution, as the code does not get so far.

### Resolve method

The resolve method is used in both queries and mutations, and it's here that responses are created.

The first three parameters to the resolve method are hard-coded:

1. The `$root` object this resolve method belongs to (can be `null`)
2. The arguments passed as `array $args` (can be an empty array)
3. The query specific GraphQL context, can be customized by overriding `\Rebing\GraphQL\GraphQLController::queryContext`

Arguments here after will be attempted to be injected, similar to how controller methods works in Laravel.

You can typehint any class that you will need an instance of.

There are two hardcoded classes which depend on the local data for the query:
- `GraphQL\Type\Definition\ResolveInfo` has information useful for field resolution process.
- `Rebing\GraphQL\Support\SelectFields` allows eager loading of related Eloquent models, see [Eager loading relationships](#eager-loading-relationships).

Example:

```php
namespace App\GraphQL\Queries;

use Closure;
use App\User;
use GraphQL;
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
namespace App\GraphQL\Queries;

use App\GraphQL\Middleware;
use Rebing\GraphQL\Support\Query;
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

#### Terminable middleware

Sometimes a middleware may need to do some work after the response has been sent to the browser.
If you define a terminate method on your middleware and your web server is using FastCGI,
the terminate method will automatically be called after the response is sent to the browser:

```php
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
    public function terminate($root, array $args, $context, ResolveInfo $info, $result): void
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
An example of Laravel's `'auth'` middleware:

```php
namespace App\GraphQL\Queries;

use Auth;
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
namespace App\GraphQL\Queries;

use Auth;
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
namespace App\GraphQL\Queries;

use Auth;
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

> **Note:** this only applies when making use of the `SelectFields` class to query Eloquent models!

You can set custom privacy attributes for every Type's Field. If a field is not
allowed, `null` will be returned. For example, if you want the user's email to
only be accessible to themselves:

```php
class UserType extends GraphQLType
{
    // ...

    public function fields(): array
    {
        return [
            'id' => [
                'type'          => Type::nonNull(Type::string()),
                'description'   => 'The id of the user'
            ],
            'email' => [
                'type'          => Type::string(),
                'description'   => 'The email of user',
                'privacy'       => function(array $args, $ctx): bool {
                    return $args['id'] == Auth::id();
                }
            ]
        ];
    }

    // ...

}
```

or you can create a class that extends the abstract GraphQL Privacy class:

```php
use Auth;
use Rebing\GraphQL\Support\Privacy;

class MePrivacy extends Privacy
{
    public function validate(array $queryArgs, $queryContext = null): bool
    {
        return $queryArgs['id'] == Auth::id();
    }
}
```

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
                'description'   => 'The id of the user'
            ],
            'email' => [
                'type'          => Type::string(),
                'description'   => 'The email of user',
                'privacy'       => MePrivacy::class,
            ]
        ];
    }

    // ...

}
```

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

```
http://homestead.app/graphql?query=query+FetchUserByID($id:Int){user(id:$id){id,email}}&variables={"id":123}
```

### Custom field

You can also define a field as a class if you want to reuse it in multiple types.

```php
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

        return 'http://placehold.it/'.$width.'x'.$height;
    }
}
```

You can then use it in your type declaration

```php
namespace App\GraphQL\Types;

use App\GraphQL\Fields\PictureField;
use App\User;
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
namespace App\GraphQL\Types;

use App\GraphQL\Fields\FormattableDate;
use App\User;
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
namespace App\GraphQL\Queries;

use Closure;
use App\User;
use GraphQL;
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
namespace App\GraphQL\Types;

use App\User;
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
                // $ctx is the GraphQL context (can be customized by overriding `\Rebing\GraphQL\GraphQLController::queryContext`
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

```
{
    "data": {
        "posts: [
            "data": [
                {"id": 3},
                {"id": 5},
                ...
            ],
            "total": 21,
            "per_page": 10
        ]
    }
}
```

Note that you need to add in the extra 'data' object when you request paginated resources as the returned data gives you
the paginated resources in a data object at the same level as the returned pagination metadata.

[Simple Pagination](https://laravel.com/docs/pagination#simple-pagination) will be used, if a query or mutation returns a `SimplePaginationType`.

```php
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
        return Type::nonNull(GraphQL::simplePaginate('posts'));
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
> - No limitations on the number of queries/mutations  
>   Currently there's no way to limit this.

Support for batching can be disabled by setting the config `batching.enable` to `false`.

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

The Enum will be registered like any other type in your schema in `config/graphq.php`:

```php
'schemas' => [
    'default' => [
        'types' => [
            EpisodeEnum::class,
        ],
```

Then use it like:
```php
namespace App\GraphQL\Types;

use Rebing\GraphQL\Support\Type as GraphQLType;

class TestType extends GraphQLType
{
    public function fields(): array
    {
        return [
            'episode_type' => [
                'type' => GraphQL::type('EpisodeEnum')
            ]
        ];
    }
}
```

### Unions

A Union is an abstract type that simply enumerates other Object Types. The value of Union Type is actually a value of one of included Object Types.

It's useful if you need to return unrelated types in the same Query. For example when implementing a search for multiple different entities.

Example for defining a UnionType:

```php
namespace App\GraphQL\Unions;

use App\Post;
use GraphQL;
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
namespace App\GraphQL\Interfaces;

use GraphQL;
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
namespace App\GraphQL\Types;

use GraphQL;
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

The Input Object will be registered like any other type in your schema in `config/graphq.php`:

```php
'schemas' => [
    'default' => [
        'types' => [
            'ReviewInput' => ReviewInput::class
        ],
```

Then use it in a mutation, like:
```php
// app/GraphQL/Type/TestMutation.php
class TestMutation extends GraphQLType {

    public function args(): array
    {
        return [
            'review' => [
                'type' => GraphQL::type('ReviewInput')
            ]
        ]
    }

}
```

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
namespace App\GraphQL\Mutations;

use Closure;
use App\User;
use GraphQL;
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
        $user->fill($args['input']));
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
using [Apollo Engine](https://www.apollographql.com/blog/schema-validation-with-apollo-engine-4032456425ba/).


```php
namespace App\GraphQL\Types;

use App\User;
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

APQ is disabled by default and can be enabled in the config via `apq.enabled=true` or by setting the environment variable `GRAPHQL_APQ_ENABLE=true`.

A persisted query is an ID or hash that can be generated on the client sent to the server instead of the entire GraphQL query string. 
This smaller signature reduces bandwidth utilization and speeds up client loading times.
Persisted queries pair especially with GET requests, enabling the browser cache and integration with a CDN.

Behind the scenes, APQ uses Laravel's cache for storing / retrieving the queries.
They are parsed by GraphQL before storing, so re-parsing them again is not necessary.
Please see the various options there for which cache, prefix, TTL, etc. to use.

> Note: it is advised to clear the cache after a deployment to accommodate for changes in your schema!

For more information see: 
 - [Apollo - Automatic persisted queries](https://www.apollographql.com/docs/apollo-server/performance/apq/) 
 - [Apollo link persisted queries - protocol](https://github.com/apollographql/apollo-link-persisted-queries#protocol)

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

Below a simple integration example with Vue/Apollo, the `createPersistedQueryLink`
automatically manages the APQ flow.

```js
// [example app.js]

require('./bootstrap');

window.Vue = require('vue');

Vue.component('example-component', require('./components/ExampleComponent.vue').default);

import { ApolloClient } from 'apollo-client';
import { ApolloLink } from 'apollo-link';
import { createHttpLink } from 'apollo-link-http';
import { createPersistedQueryLink } from 'apollo-link-persisted-queries';
import { InMemoryCache } from 'apollo-cache-inmemory';
import VueApollo from 'vue-apollo';

const httpLinkWithPersistedQuery = createPersistedQueryLink().concat(createHttpLink({
    uri: '/graphql',
}));

// Create the apollo client
const apolloClient = new ApolloClient({
    link: ApolloLink.from([httpLinkWithPersistedQuery]),
    cache: new InMemoryCache(),
    connectToDevTools: true,
})

const apolloProvider = new VueApollo({
    defaultClient: apolloClient,
});

Vue.use(VueApollo);

const app = new Vue({
    el: '#app',
    apolloProvider,
});
```
```vue 
<!-- [example TestComponent.vue] -->

<template>
    <div>
        <p>Test APQ</p>
        <p>-> <span v-if="$apollo.queries.hello.loading">Loading...</span>{{ hello }}</p>
    </div>
</template>

<script>
    import gql from 'graphql-tag';
    export default {
        apollo: {
            hello: gql`query{hello}`,
        },
        mounted() {
            console.log('Component mounted.')
        }
    }
</script>
```

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

- `route`\
  Holds all the configuration for the route group. Each schema will be available
  via its name as a dedicated route.
  - `prefix`\
    The route prefix to your GraphQL endpoint without the leading `/`.\
    The default makes the API available via `/graphql`
  - `controller`\
    Allows overriding the default controller class, in case you want to extend or
    replace the existing one (also supports `array` format).
  - `middleware`\
    Global GraphQL middleware applying in case no schema-specific middleware was
    provided
  - `group_attributes`\
    Additional route group attributes
- `default_schema`\
  The name of the default schema used, when none is provided via the route
- `batching`\
  - 'enable'\
    Whether to support GraphQL batching or not
- `lazyload_types`\
  The types will be loaded on demand. Enabled by default as it improves
  performance. Cannot be used with type aliasing.
- `error_formatter`\
  This callable will be passed the Error object for each errors GraphQL catch.
  The method should return an array representing the error.
- `errors_handler`\
  Custom Error Handling. The default handler will pass exceptions to laravel
  Error Handling mechanism.
- `security`\
  Various options to limit the query complexity and depth, see docs at
  https://webonyx.github.io/graphql-php/security/
  - `query_max_complexity`
  - `query_max_depth`
  - `disable_introspection`
- `pagination_type`\
  You can define your own pagination type.
- `simple_pagination_type`\
  You can define your own simple pagination type.
- `graphiql`\
  Config for GraphiQL (see (https://github.com/graphql/graphiql)
  - `prefix`\
    The route prefix
  - `controller`\
    The controller / method to handle the route
  - `middleware`\
    Any middleware to be run before invoking the controller
  - `view`\
    Which view to use
  - `display`\
    Whether to enable it or not.\
    **Note:** it's recommended to disable this in production!
- `defaultFieldResolver`\
  Overrides the default field resolver, see http://webonyx.github.io/graphql-php/data-fetching/#default-field-resolver
- `headers`\
  Any headers that will be added to the response returned by the default controller
- `json_encoding_options`\
  Any JSON encoding options when returning a response from the default controller
- `apq`\
  Automatic Persisted Queries (APQ)
  - `enable`\
    It's disabled by default.
  - `cache_driver`\
    Which cache driver to use.
  - `cache_prefix`\
    The cache prefix to use.
  - `cache_ttl`\
    How long to cache the queries.
- `detect_unused_variables`\
  If enabled, variables provided but not consumed by the query will throw an error

## Guides

### Upgrading from v1 to v2

Although version 2 builds on the same code base and does not radically change how the library itself works, many things were improved, sometimes leading to incompatible changes.

- Step 0: make a backup!
- Re-publish the configuration file to learn about all the new settings
- The order and arguments/types for resolvers has changed:
  - before: `resolve($root, $array, SelectFields $selectFields, ResolveInfo $info)`
  - after: `resolve($root, $array, $context, ResolveInfo $info, Closure $getSelectFields)`
  - If you now want to use SelectFields, you've to first request it: `$selectFields = $getSelectFields();`. The primary reason for this is performance. SelectFields is an optional feature but consumes resources to traverse the GraphQL request AST and introspect all the types for their configuration to apply its magic. In the past it was always constructed and thus consumed resources, even when not requested. This has been changed to an explicit form.
- Many method signature declarations changed to improve type safety, which have to be adapted:
  - The signature of the method fields changed:
    - from `public function fields()`
    - to `public function fields(): array`
  - The signature of the method toType changed:
    - from `public function toType()`
    - to `public function toType(): \GraphQL\Type\Definition\Type`
  - The signature of the method getFields changed:
    - from `public function getFields()`
    - to `public function getFields(): array`
  - The signature of the method interfaces changed:
    - from `public function interfaces()`
    - to `public function interfaces(): array`
  - The signature of the method types changed:
    - from `public function types()`
    - to `public function types(): array`
  - The signature of the method type changed:
    - from `public function type()`
    - to `public function type(): \GraphQL\Type\Definition\Type`
  - The signature of the method args changed:
    - from `public function args()`
    - to `public function args(): array`
  - The signature of the method queryContext changed:
    - from `protected function queryContext($query, $variables, $schema)`
    - to `protected function queryContext()`
  - The signature of the controller method query changed:
    - from `function query($query, $variables = [], $opts = [])`
    - to `function query(string $query, ?array $variables = [], array $opts = []): array`
  - If you're using custom Scalar types:
    - the signature of the method parseLiteral changed (due to upgrade of the webonyx library):
      - from `public function parseLiteral($ast)`
      - to `public function parseLiteral($valueNode, ?array $variables = null)`
- The `UploadType` now has to be added manually to the `types` in your schema if you want to use it. The `::getInstance()` method is gone, you simple reference it like any other type via `GraphQL::type('Upload')`.
- Follow Laravel convention and use plural for namespaces (e.g. new queries are placed in `App\GraphQL\Queries`, not `App\GraphQL\Query` anymore); the respective `make` commands have been adjusted. This will not break any existing code, but code generates will use the new schema.
- Be sure to read the [Changelog](CHANGELOG.md) for more details

### Migrating from Folklore
https://github.com/folkloreinc/laravel-graphql, formerly also known as https://github.com/Folkloreatelier/laravel-graphql

Both code bases are very similar and, depending on your level of customization, the migration may be very quick.

Note: this migration is written with version 2.* of this library in mind.

The following is not a bullet-proof list but should serve as a guide. It's not an error if you don't need to perform certain steps.

**Make a backup before proceeding!**

- `composer remove folklore/graphql`
- if you've a custom ServiceProvider or did include it manually, remove it. The point is that the existing GraphQL code should not be triggered to run.
- `composer require rebing/graphql-laravel`
- Publish `config/graphql.php` and adapt it (prefix, middleware, schemas, types, mutations, queries, security settings, graphiql)
  - Removed settings
    - `domain`
    - `resolvers`
  - `schema` (default schema) renamed to `default_schema`
  - `middleware_schema` does not exist, it's defined within a `schema.<name>.middleware` now
- Change namespace references:
  - from `Folklore\`
  - to `Rebing\`
- See [Upgrade guide from v1 to v2 for all the function signature changes](#upgrading-from-v1-to-v2)
- The trait `ShouldValidate` does not exist anymore; the provided features are baked into `Field`
- The first argument to the resolve method for queries/mutations is now `null` (previously its default was an empty array)

## Performance considerations

### Lazy loading of types

Lazy loading of types is a way of improving the start up performance.

If you are declaring types using aliases, this is not supported and you need to
set `lazyload_types` set to `false`.

#### Example of aliasing **not** supported by lazy loading

I.e. you cannot have a query class `ExampleQuery` with the `$name` property
`example` but register it with a different one; this will **not** work:

```php
'query' => [
    'aliasedExample' => ExampleQuery::class,
],
```

### Wrap Types

You can wrap types to add more information to the queries and mutations. Similar as the pagination is working you can do the same with your extra data that you want to inject ([see test examples](https://github.com/rebing/graphql-laravel/tree/master/tests/Unit/WithTypeTests)). For instance, in your query:

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

## GraphQL testing clients
 - [Firecamp](https://firecamp.io/graphql)
 - [GraphiQL](https://github.com/graphql/graphiql)
