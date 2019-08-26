# Laravel GraphQL

[![Latest Stable Version](https://poser.pugx.org/rebing/graphql-laravel/v/stable)](https://packagist.org/packages/rebing/graphql-laravel)
[![codecov](https://codecov.io/gh/rebing/graphql-laravel/branch/master/graph/badge.svg)](https://codecov.io/gh/rebing/graphql-laravel)
[![Build Status](https://travis-ci.org/rebing/graphql-laravel.svg?branch=master)](https://travis-ci.org/rebing/graphql-laravel)
[![Style CI](https://styleci.io/repos/68595316/shield)](https://styleci.io/repos/68595316)
[![License](https://poser.pugx.org/rebing/graphql-laravel/license)](https://packagist.org/packages/rebing/graphql-laravel)
[![Get on Slack](https://img.shields.io/badge/slack-join-orange.svg)](https://join.slack.com/t/rebing-graphql/shared_invite/enQtNTE5NjQzNDI5MzQ4LWVjMTMxNzIyZjBlNTFhZGQ5MDVjZDAwZDNjODA3ODE2NjdiOGJkMjMwMTZkZmNhZjhiYTE1MjEyNDk0MWJmMzk)

### Note: these are the docs for 2.*, [please see the `v1` branch for the 1.* docs](https://github.com/rebing/graphql-laravel/tree/v1#laravel-graphql)

Uses Facebook GraphQL with Laravel 5.5+. It is based on the PHP implementation [here](https://github.com/webonyx/graphql-php). You can find more information about GraphQL in the [GraphQL Introduction](http://facebook.github.io/react/blog/2015/05/01/graphql-introduction.html) on the [React](http://facebook.github.io/react) blog or you can read the [GraphQL specifications](https://facebook.github.io/graphql/). This is a work in progress.

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
* Support for file uploads

## Installation

#### Dependencies:

* [Laravel 5.5+](https://github.com/laravel/laravel) or [Lumen](https://github.com/laravel/lumen)
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
```php
config/graphql.php
```

##### Lumen (experimental!)

**1.** Add the following service provider to the `bootstrap/app.php` file
```php
$app->register(Rebing\GraphQL\GraphQLLumenServiceProvider::class);
```

**2.** Publish the configuration file
```bash
$ php artisan graphql:publish
```

**3.** Add the configuration to the `bootstrap/app.php` file
    *Important:* this needs to be before the registration of the service provider
```php
$app->configure('graphql');
...
$app->register(Rebing\GraphQL\GraphQLLumenServiceProvider::class);
```

**4.** Review the configuration file
```php
config/graphql.php
```

The default GraphiQL view makes use of the global `csrf_token()` helper function.
Out of the box, this function is not available in Lumen.

To work this around:
- Point to your local GraphiQL view: change `graphql.view` to `'vendor/graphql/graphiql'`
- Modify your file `resources/views/vendor/graphql/graphiql.php` and remove the call

## Usage

- [Schemas](#schemas)
- [Creating a query](#creating-a-query)
- [Creating a mutation](#creating-a-mutation)
- [Adding validation to mutation](#adding-validation-to-mutation)
- [File uploads](#file-uploads)
- [Authorization](#authorization)
- [Privacy](#privacy)
- [Query variables](#query-variables)
- [Custom field](#custom-field)
- [Eager loading relationships](#eager-loading-relationships)
- [Type relationship query](#type-relationship-query)
- [Pagination](#pagination)
- [Batching](#batching)
- [Scalar Types](#scalar-types)
- [Enums](#enums)
- [Unions](#unions)
- [Interfaces](#interfaces)
- [Input Object](#input-object)
- [JSON Columns](#json-columns)
- [Field deprecation](#field-deprecation)
- [Default Field Resolver](#default-field-resolver)
- [Upgrading from v1 to v2](#upgrading-from-v1-to-v2)
- [Migrating from Folklore](#migrating-from-folklore)
- [Performance considerations](#performance-considerations)

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
            'profile' => App\GraphQL\Queries\ProfileQuery::class
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
                // Use 'alias', if the database column is different from the type name.
                // This is supported for discrete values as well as relations.
                // - you can also use `DB::raw()` to solve more complex issues
                // - or a callback returning the value (string or `DB::raw()` result)
                'alias' => 'user_id',
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
    'user' => App\GraphQL\Types\UserType::class
]
```

You could also add the type with the `GraphQL` Facade, in a service provider for example.

```php
GraphQL::addType(\App\GraphQL\Types\UserType::class, 'user');
```

Then you need to define a query that returns this type (or a list). You can also specify arguments that you can use in the resolve method.
```php
<?php

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
        'name' => 'Users query'
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('user'));
    }

    public function args(): array
    {
        return [
            'id' => ['name' => 'id', 'type' => Type::string()],
            'email' => ['name' => 'email', 'type' => Type::string()]
        ];
    }

    public function resolve($root, $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
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
            'users' => App\GraphQL\Queries\UsersQuery::class
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

A mutation is like any other query. It accepts arguments (which will be used to do the mutation) and returns an object of a certain type.

For example, a mutation to update the password of a user. First you need to define the Mutation:

```php
<?php

namespace App\GraphQL\Mutations;

use CLosure;
use App\User;
use GraphQL;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Mutation;

class UpdateUserPasswordMutation extends Mutation
{
    protected $attributes = [
        'name' => 'UpdateUserPassword'
    ];

    public function type(): Type
    {
        return GraphQL::type('user');
    }

    public function args(): array
    {
        return [
            'id' => ['name' => 'id', 'type' => Type::nonNull(Type::string())],
            'password' => ['name' => 'password', 'type' => Type::nonNull(Type::string())]
        ];
    }

    public function resolve($root, $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
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
            'updateUserPassword' => App\GraphQL\Mutations\UpdateUserPasswordMutation::class
        ],
        // ...
    ]
]
```

You should then be able to use the following query on your endpoint to do the mutation:

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

#### Adding validation to a mutation

It is possible to add validation rules to a mutation. It uses the Laravel `Validator` to perform validation against the `$args`.

When creating a mutation, you can add a method to define the validation rules that apply by doing the following:

```php
<?php

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
        'name' => 'UpdateUserEmail'
    ];

    public function type(): Type
    {
        return GraphQL::type('user');
    }

    public function args(): array
    {
        return [
            'id' => ['name' => 'id', 'type' => Type::string()],
            'email' => ['name' => 'email', 'type' => Type::string()]
        ];
    }

    protected function rules(array $args = []): array
    {
        return [
            'id' => ['required'],
            'email' => ['required', 'email']
        ];
    }

    public function resolve($root, $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
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
<?php
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
````


#### File uploads

This library provides a middleware compliant with the spec at https://github.com/jaydenseric/graphql-multipart-request-spec .

You have to add the `\Rebing\GraphQL\Support\UploadType` first to your `config/graphql` schema types definition:

```php
'types' => [
    \Rebing\GraphQL\Support\UploadType::class,
],
```

It is relevant that you send the request as `multipart/form-data`:

> **WARNING:** when you are uploading files, Laravel will use FormRequest - it means
> that middlewares which are changing request, will not have any effect.

```php
<?php

namespace App\GraphQL\Mutations;

use Closure;
use GraphQL;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Mutation;

class UserProfilePhotoMutation extends Mutation
{
    protected $attributes = [
        'name' => 'UpdateUserProfilePhoto'
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

    public function resolve($root, $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $file = $args['profilePicture'];

        // Do something with file here...
    }
}
```

Note: You can test your file upload implementation using [Altair](https://altair.sirmuel.design/) as explained [here](https://sirmuel.design/working-with-file-uploads-using-altair-graphql-d2f86dc8261f).

### Authorization

For authorization similar to Laravel's Request (or middleware) functionality, we can override the `authorize()` function in a Query or Mutation.
An example of Laravel's `'auth'` middleware:

```php
use Auth;

class UsersQuery extends Query
{
    public function authorize(array $args): bool
    {
        // true, if logged in
        return ! Auth::guest();
    }

    // ...
}
```

Or we can make use of arguments passed via the GraphQL query:

```php
use Auth;

class UsersQuery extends Query
{
    public function authorize(array $args): bool
    {
        if (isset($args['id'])) {
            return Auth::id() == $args['id'];
        }

        return true;
    }

    // ...
}
```

### Privacy

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
                'privacy'       => function(array $args): bool {
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
    public function validate(array $queryArgs): bool
    {
        return $args['id'] == Auth::id();
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

### Query Variables

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

When you query the GraphQL endpoint, you can pass a `params` (or whatever you define in the config) parameter.

```
http://homestead.app/graphql?query=query+FetchUserByID($id:Int){user(id:$id){id,email}}&params={"id":123}
```

Notice that your client side framework might use another parameter name than `params`.
You can customize the parameter name to anything your client is using by adjusting
the `params_key` in the `graphql.php` configuration file.

### Custom field

You can also define a field as a class if you want to reuse it in multiple types.

```php
<?php

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

    protected function resolve($root, $args)
    {
        $width = isset($args['width']) ? $args['width']:100;
        $height = isset($args['height']) ? $args['height']:100;

        return 'http://placehold.it/'.$width.'x'.$height;
    }
}
```

You can then use it in your type declaration

```php
<?php

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

### Eager loading relationships

The fifth argument passed to a query's resolve method is a Closure which returns
an instance of `Rebing\GraphQL\Support\SelectFields` which you can use to retrieve keys
from the request. The following is an example of using this information
to eager load related Eloquent models.

This way only the required fields will be queried from the database.

The Closure accepts an optional parameter for the depth of the query to analyse.

Your Query would look like:

```php
<?php

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
        'name' => 'Users query'
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('user'));
    }

    public function args(): array
    {
        return [
            'id' => ['name' => 'id', 'type' => Type::string()],
            'email' => ['name' => 'email', 'type' => Type::string()]
        ];
    }

    public function resolve($root, $args, $context, ResolveInfo $info, Closure $getSelectFields)
    {
        // $info->getFieldSelection($depth = 3);

        // If your GraphQL query exceeds the default nesting query, you can increase it here:
        // $fields = $getSelectFields(11);

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

The attribute can be a comma separted string or an array of attribues to
always include.

```php
<?php

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
                'type'          => Type::listOf(GraphQL::type('post')),
                'description'   => 'A list of posts written by the user',
                'args'          => [
                    'date_from' => [
                        'type' => Type::string(),
                    ],
                 ],
                // $args are the local arguments passed to the relation
                // $query is the relation builder object
                // $ctx is the GraphQL context (can be customized by overriding `\Rebing\GraphQL\GraphQLController::queryContext`
                'query'         => function(array $args, $query, $ctx) {
                    return $query->where('posts.created_at', '>', $args['date_from']);
                }
            ]
        ];
    }
}
```

### Pagination

Pagination will be used, if a query or mutation returns a `PaginationType`.
Note that you have to manually handle the limit and page values:

```php
class PostsQuery extends Query
{
    public function type(): \GraphQL\Type\Definition\Type
    {
        return GraphQL::paginate('posts');
    }

    // ...

    public function resolve($root, $args, $context, ResolveInfo $info, Closure $getSelectFields)
    {
        $fields = $getSelectFields();
        return Post
            ::with($fields->getRelations())
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

### Batching

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

For systems sending multiple requests at once, this can really help performance by batching together queries that will be made
within a certain interval of time.

There are tools that help with this and can handle the batching for you, e.g [Apollo](http://www.apollodata.com/)

### Scalar Types

GraphQL comes with built-in scalar types for string, int, boolean, etc. It's possible to create custom scalar types to special purpose fields.

An example could be a link: instead of using `Type::string()` you could create a scalar type `Link` and reference it with `GraphQL::type('Link')`.

The benefits would be:

- a dedicated description so you can give more meaning/purpose to a field than just call it a string type
- explicit conversion logic for the followig steps:
  - converting from the internal logic to the serialized GraphQL output (`serialize`)
  - query/field input argument conversion (`parseLiteral`)
  - when passed as variables to your query (`parseValue`)

This also means validation logic can be added within these methods to _ensure_ that the value delivered/received is e.g. a true link.

A scalar type has to implement all the methods; you can quick start this with `artisan make:graphql:scalar <typename>`. Then just add the scalar to your existing types in the schema.

For more advanced use, please [refer to the official documentation regarding scalar types](https://webonyx.github.io/graphql-php/type-system/scalar-types).

### Enums

Enumeration types are a special kind of scalar that is restricted to a particular set of allowed values.
Read more about Enums [here](http://graphql.org/learn/schema/#enumeration-types)

First create an Enum as an extension of the GraphQLType class:
```php
<?php

namespace App\GraphQL\Enums;

use Rebing\GraphQL\Support\EnumType;

class EpisodeEnum extends EnumType
{
    protected $attributes = [
        'name' => 'Episode',
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

Register the Enum in the `types` array of the `graphql.php` config file:

```php
'types' => [
    'EpisodeEnum' => EpisodeEnum::class
];
```

Then use it like:
```php
<?php

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
<?php

namespace App\GraphQL\Unions;

use App\Post;
use GraphQL;
use Rebing\GraphQL\Support\UnionType;

class SearchResultUnion extends UnionType
{
    protected $attributes = [
        'name' => 'SearchResult',
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

You can use interfaces to abstract a set of fields. Read more about Interfaces [here](http://graphql.org/learn/schema/#interfaces)

An implementation of an interface:

```php
<?php

namespace App\GraphQL\Interfaces;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\InterfaceType;

class CharacterInterface extends InterfaceType
{
    protected $attributes = [
        'name' => 'Character',
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
<?php

namespace App\GraphQL\Types;

use GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;
use GraphQL\Type\Definition\Type;

class HumanType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Human',
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

#### Sharing Interface fields

Since you often have to repeat many of the field definitons of the Interface in the concrete types, it makes sense to share the definitions of the Interface.
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

Input Object types allow you to create complex inputs. Fields have no args or resolve options and their type must be input type. You can add rules option if you want to validate input data.
Read more about Input Object [here](https://graphql.org/learn/schema/#input-types)

First create an InputObjectType as an extension of the GraphQLType class:
```php
<?php

namespace App\GraphQL\InputObject;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\InputType;

class ReviewInput extends InputType
{
    protected $attributes = [
        'name' => 'ReviewInput',
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
                'rules' => ['min:0', 'max:5']
            ]
        ];
    }
}
```
Register the Input Object in the `types` array of the `graphql.php` config file:

```php
'types' => [
    'ReviewInput' => ReviewInput::class
];
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

### JSON Columns

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
                'type'          => Type::listOf(GraphQL::type('post')),
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

#### Field deprecation

Sometimes you would want to deprecate a field but still have to maintain backward compatibility
until clients completely stop using that field. You can deprecate a field using
[directive](https://www.apollographql.com/docs/graphql-tools/schema-directives.html). If you add `deprecationReason`
to field attributes it will become marked as deprecated in GraphQL documentation. You can validate schema on client
using [Apollo Engine](https://blog.apollographql.com/schema-validation-with-apollo-engine-4032456425ba).


```php
<?php

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

#### Default Field Resolver

It's possible to override the default field resolver provided by the underlying
webonyx/graphql-php library using the config option `defaultFieldResolver`.

You can define any valid callable (static class method, closure, etc.) for it:

```php
'defaultFieldResolver' => [Your\Klass::class, 'staticMethod'],
```

The parameters received are your regular "resolve" function signature.

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
    - the signature of the method parseLiteral changed (due to upgrade of the webonxy library):
      - from `public function parseLiteral($ast)`
      - to `public function parseLiteral($valueNode, ?array $variables = null)`
- The `UploadType` now has to be added manually to the `types` in your schema if you want to use it. The `::getInstance()` method is gone, you simple reference it like any other type via `GraphQL::type('Upload')`.
- Follow Laravel convention and use plural for namspaces (e.g. new queries are placed in `App\GraphQL\Queries`, not `App\GraphQL\Query` anymore); the respective `make` commands have been adjusted. This will not break any existing code, but code generates will use the new schema.
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
  - `schema` (defaul schema) renamed to `default_schema`
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

If you are declaring types using aliases it is not supported.
If that is not the case, you can enable it with `lazyload_types` set to `true`.

#### Example of aliasing **not** supported by lazy loading

I.e. you cannot have a query class `ExampleQuery` with the `$name` property
`example` but register it with a different one; this will **not** work:

```php
'query' => [
    'aliasedEXample' => ExampleQuery::class,
],
```
