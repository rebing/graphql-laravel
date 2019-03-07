# Laravel GraphQL

[![Latest Stable Version](https://poser.pugx.org/rebing/graphql-laravel/v/stable)](https://packagist.org/packages/rebing/graphql-laravel)
[![Build Status](https://travis-ci.org/rebing/graphql-laravel.svg?branch=master)](https://travis-ci.org/mfn/graphql-laravel)
[![License](https://poser.pugx.org/rebing/graphql-laravel/license)](https://packagist.org/packages/rebing/graphql-laravel)
[![Get on Slack](https://img.shields.io/badge/slack-join-orange.svg)](https://join.slack.com/t/rebing-graphql/shared_invite/enQtNTE5NjQzNDI5MzQ4LWVjMTMxNzIyZjBlNTFhZGQ5MDVjZDAwZDNjODA3ODE2NjdiOGJkMjMwMTZkZmNhZjhiYTE1MjEyNDk0MWJmMzk)


Uses Facebook GraphQL with Laravel 5. It is based on the PHP implementation [here](https://github.com/webonyx/graphql-php). You can find more information about GraphQL in the [GraphQL Introduction](http://facebook.github.io/react/blog/2015/05/01/graphql-introduction.html) on the [React](http://facebook.github.io/react) blog or you can read the [GraphQL specifications](https://facebook.github.io/graphql/). This is a work in progress.

This package is compatible with Eloquent models or any other data source.
* Allows creating **queries** and **mutations** as request endpoints
* Custom **middleware** can be defined for each query/mutation
* Queries return **types**, which can have custom **privacy** settings.
* The queried fields will have the option to be retrieved **dynamically** from the database with the help of the `SelectFields` class.

It offers following features and improvements over the original package by
[Folklore](https://github.com/Folkloreatelier/laravel-graphql):
* Per-operation authorization
* Per-field callback defining its visibility (e.g. hiding from unauthenticated users)
* `SelectFields` abstraction available in `resolve()`, allowing for advanced eager loading
  and thus dealing with n+1 problems
* Pagination support
* Server-side support for [query batching](https://blog.apollographql.com/batching-client-graphql-queries-a685f5bcd41b)

## Installation

#### Dependencies:

* [Laravel 5.x](https://github.com/laravel/laravel) or [Lumen](https://github.com/laravel/lumen)
* [GraphQL PHP](https://github.com/webonyx/graphql-php)


#### Installation:

**-** Require the package via Composer
```bash
composer require rebing/graphql-laravel
```

##### Laravel 5.5+

**1.** Laravel 5.5+ will autodiscover the package, for older versions add the
following service provider
```php
Rebing\GraphQL\GraphQLServiceProvider::class,
```

and alias
```php
'GraphQL' => 'Rebing\GraphQL\Support\Facades\GraphQL',
```

in your `config/app.php` file.

**2.** Publish the configuration file
```bash
$ php artisan vendor:publish --provider="Rebing\GraphQL\GraphQLServiceProvider"
```

**3.** Review the configuration file
```
config/graphql.php
```

##### Lumen (experimental!)

**1.** Add the following service provider to the `bootstrap/app.php` file
```php
$app->register(Rebing\GraphQL\GraphQLLumenServiceProvider::class);
```

**2.** Publish the configuration file
```bash
$ php artisan graphql:publish"
```

**3.** Add the configuration to the `bootstrap/app.php` file
    *Important:* this needs to be before the registration of the service provider
```php
$app->configure('graphql');
...
$app->register(Rebing\GraphQL\GraphQLLumenServiceProvider::class);
```

**4.** Review the configuration file
```
config/graphql.php
```

## Usage

- [Schemas](#schemas)
- [Creating a query](#creating-a-query)
- [Creating a mutation](#creating-a-mutation)
- [Adding validation to mutation](#adding-validation-to-mutation)
- [File uploads](#file-uploads)

##### Advanced Usage

- [Authorization](docs/advanced.md#authorization)
- [Privacy](docs/advanced.md#privacy)
- [Query variables](docs/advanced.md#query-variables)
- [Custom field](docs/advanced.md#custom-field)
- [Eager loading relationships](docs/advanced.md#eager-loading-relationships)
- [Type relationship query](docs/advanced.md#type-relationship-query)
- [Pagination](docs/advanced.md#pagination)
- [Batching](docs/advanced.md#batching)
- [Enums](docs/advanced.md#enums)
- [Unions](docs/advanced.md#unions)
- [Interfaces](docs/advanced.md#interfaces)
- [Input Object](docs/advanced.md#input-object)
- [JSON Columns](docs/advanced.md#json-columns)

### Schemas

Schemas are required for defining GraphQL endpoints. You can define multiple schemas and assign different **middleware** to them,
in addition to the global middleware. For example:

```php
'schema' => 'default_schema',

'schemas' => [
    'default' => [
        'query' => [
            'example_query' => ExampleQuery::class,
        ],
        'mutation' => [
            'example_mutation'  => ExampleMutation::class,
        ],
    ],
    'user' => [
        'query' => [
            'profile' => App\GraphQL\Query\ProfileQuery::class
        ],
        'mutation' => [
        
        ],
        'middleware' => ['auth'],
    ],
],
```

### Creating a query

First you need to create a type. The Eloquent Model is only required, if specifying relations.

> **Note:** The `selectable` key is required, if it's a non-database field or not a relation

```php
<?php

namespace App\GraphQL\Type;

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

    public function fields()
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The id of the user',
                'alias' => 'user_id', // Use 'alias', if the database column is different from the type name
            ],
            'email' => [
                'type' => Type::string(),
                'description' => 'The email of user',
            ],
            // Uses the 'getIsMeAttribute' function on our custom User model
            'isMe' => [
                'type' => Type::boolean(),
                'description' => 'True, if the queried user is the current user',
                'selectable' => false, // Does not try to query this from the database
            ]
        ];
    }

    // If you want to resolve the field yourself, you can declare a method
    // with the following format resolve[FIELD_NAME]Field()
    protected function resolveEmailField($root, $args)
    {
        return strtolower($root->email);
    }    
}
```

Add the type to the `config/graphql.php` configuration file

```php
'types' => [
    'user' => App\GraphQL\Type\UserType::class
]
```

You could also add the type with the `GraphQL` Facade, in a service provider for example.

```php
GraphQL::addType(\App\GraphQL\Type\UserType::class, 'user');
```

Then you need to define a query that returns this type (or a list). You can also specify arguments that you can use in the resolve method.
```php
<?php

namespace App\GraphQL\Query;

use App\User;
use GraphQL;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Query;

class UsersQuery extends Query
{
    protected $attributes = [
        'name' => 'Users query'
    ];

    public function type()
    {
        return Type::listOf(GraphQL::type('user'));
    }

    public function args()
    {
        return [
            'id' => ['name' => 'id', 'type' => Type::string()],
            'email' => ['name' => 'email', 'type' => Type::string()]
        ];
    }

    public function resolve($root, $args)
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
            'users' => App\GraphQL\Query\UsersQuery::class
        ],
        // ...
    ]
]
```

And that's it. You should be able to query GraphQL with a request to the url `/graphql` (or anything you choose in your config). Try a GET request with the following `query` input

```
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

A mutation is like any other query. It accepts arguments (which will be used to do the mutation) and returns an object of a certain type.

For example, a mutation to update the password of a user. First you need to define the Mutation:

```php
<?php

namespace App\GraphQL\Mutation;

use App\User;
use GraphQL;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Mutation;    

class UpdateUserPasswordMutation extends Mutation
{
    protected $attributes = [
        'name' => 'UpdateUserPassword'
    ];

    public function type()
    {
        return GraphQL::type('user');
    }

    public function args()
    {
        return [
            'id' => ['name' => 'id', 'type' => Type::nonNull(Type::string())],
            'password' => ['name' => 'password', 'type' => Type::nonNull(Type::string())]
        ];
    }

    public function resolve($root, $args)
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
            'updateUserPassword' => App\GraphQL\Mutation\UpdateUserPasswordMutation::class
        ],
        // ...
    ]
]
```

You should then be able to use the following query on your endpoint to do the mutation:

```
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

#### Adding validation to a mutation

It is possible to add validation rules to a mutation. It uses the Laravel `Validator` to perform validation against the `$args`.

When creating a mutation, you can add a method to define the validation rules that apply by doing the following:

```php
<?php

namespace App\GraphQL\Mutation;

use App\User;
use GraphQL;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Mutation;

class UpdateUserEmailMutation extends Mutation
{
    protected $attributes = [
        'name' => 'UpdateUserEmail'
    ];

    public function type()
    {
        return GraphQL::type('user');
    }

    public function args()
    {
        return [
            'id' => ['name' => 'id', 'type' => Type::string()],
            'email' => ['name' => 'email', 'type' => Type::string()]
        ];
    }

    public function rules(array $args = [])
    {
        return [
            'id' => ['required'],
            'email' => ['required', 'email']
        ];
    }

    public function resolve($root, $args)
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

Alternatively, you can define rules on each argument:

```php	
class UpdateUserEmailMutation extends Mutation
{

    //...

    public function args()
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

When you execute a mutation, it will return any validation errors that occur. Since the GraphQL specification defines a certain format for errors, the validation errors are added to the error object as a extra `validation` attribute. To find the validation error, you should check for the error with a `message` equals to `'validation'`, then the `validation` attribute will contain the normal errors messages returned by the Laravel Validator:

```json
{
    "data": {
        "updateUserEmail": null
    },
    "errors": [
        {
            "message": "validation",
            "locations": [
                {
                    "line": 1,
                    "column": 20
                }
            ],
            "validation": {
                "email": [
                    "The email is invalid."
                ]
            }
        }
    ]
}
```

The validation errors returned can be customised by overriding the `validationErrorMessages`
method on the mutation. This method should return an array of custom validation messages
in the same way documented by Laravel's validation. For example, to check an `email`
argument doesn't conflict with any existing data, you could perform the following:

> **Note:** the keys should be in `field_name`.`validator_type` format so you can
> return specific errors per validation type.

````php
public function validationErrorMessages ($args = []) 
{
    return [
        'name.required' => 'Please enter your full name',
        'name.string' => 'Your name must be a valid string',
        'email.required' => 'Please enter your email address',
        'email.email' => 'Please enter a valid email address',
        'email.exists' => 'Sorry, this email address is already in use',                     
    ];
}
````


#### File uploads

For uploading new files just use `UploadType`. This support of uploading files is
based on https://github.com/jaydenseric/graphql-multipart-request-spec
so you have to upload them as multipart form:

> **WARNING:** when you are uploading files, Laravel will use FormRequest - it means
> that middlewares which are changing request, will not have any effect.

```php
<?php

use GraphQL;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\UploadType;
use Rebing\GraphQL\Support\Mutation;

class UserProfilePhotoMutation extends Mutation
{
    protected $attributes = [
        'name' => 'UpdateUserProfilePhoto'
    ];

    public function type()
    {
        return GraphQL::type('user');
    }

    public function args()
    {
        return [
            'profilePicture' => [
                'name' => 'profilePicture',
                'type' => UploadType::getInstance(),
                'rules' => ['required', 'image', 'max:1500'],
            ],
        ];
    }

    public function resolve($root, $args)
    {
        $file = $args['profilePicture'];

        // Do something with file here...
    }
}
```

Note: You can test your file upload implementation using [Altair](https://altair.sirmuel.design/) as explained [here](https://sirmuel.design/working-with-file-uploads-using-altair-graphql-d2f86dc8261f).

### Advanced usage
- [Authorization](docs/advanced.md#authorization)
- [Privacy](docs/advanced.md#privacy)
- [Query variables](docs/advanced.md#query-variables)
- [Custom field](docs/advanced.md#custom-field)
- [Eager loading relationships](docs/advanced.md#eager-loading-relationships)
- [Type relationship query](docs/advanced.md#type-relationship-query)
- [Pagination](docs/advanced.md#pagination)
- [Batching](docs/advanced.md#batching)
- [Enums](docs/advanced.md#enums)
- [Unions](docs/advanced.md#unions)
- [Interfaces](docs/advanced.md#interfaces)
- [Input Object](docs/advanced.md#input-object)
- [JSON Columns](docs/advanced.md#json-columns)
- [Field deprecation](docs/advanced.md#field-deprecation)