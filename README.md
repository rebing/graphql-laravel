# Laravel GraphQL

[![Latest Stable Version](https://poser.pugx.org/rebing/graphql-laravel/v/stable)](https://packagist.org/packages/rebing/graphql-laravel)
[![License](https://poser.pugx.org/rebing/graphql-laravel/license)](https://packagist.org/packages/rebing/graphql-laravel)
[![Get on Slack](https://img.shields.io/badge/slack-join-orange.svg)](https://join.slack.com/t/rebing-graphql/shared_invite/enQtNTE5NjQzNDI5MzQ4LTdhNjk0ZGY1N2U1YjE4MGVlYmM2YTc2YjQ0MmIwODY5MWMwZWIwYmY1MWY4NTZjY2Q5MzdmM2Q3NTEyNDYzZjc)

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

- [Laravel GraphQL](#laravel-graphql)
  - [Installation](#installation)
      - [Dependencies:](#dependencies)
      - [Installation:](#installation)
        - [Laravel 5.5+](#laravel-55)
        - [Lumen (experimental!)](#lumen-experimental)
  - [Usage](#usage)
    - [Schemas](#schemas)
    - [Creating a query](#creating-a-query)
    - [Creating a mutation](#creating-a-mutation)
      - [Adding validation to a mutation](#adding-validation-to-a-mutation)
      - [File uploads](#file-uploads)
    - [Resolve method](#resolve-method)
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
      - [Sharing interface fields](#sharing-interface-fields)
    - [Input Object](#input-object)
    - [Type modifiers](#type-modifiers)
    - [Field and input alias](#field-and-input-alias)
    - [JSON columns](#json-columns)
    - [Field deprecation](#field-deprecation)
    - [Default field resolver](#default-field-resolver)
    - [Macros](#macros)
  - [Guides](#guides)
    - [Upgrading from v1 to v2](#upgrading-from-v1-to-v2)
    - [Migrating from Folklore](#migrating-from-folklore)
  - [Performance considerations](#performance-considerations)
    - [Lazy loading of types](#lazy-loading-of-types)
      - [Example of aliasing **not** supported by lazy loading](#example-of-aliasing-not-supported-by-lazy-loading)
    - [Wrap Types](#wrap-types)
  - [GraphQL testing clients](#graphql-testing-clients)

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
                'resolve' => function($root, $args) {
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
            'email' => ['required', 'email'],
            'password' => function($inputArguments, $mutationArguments) {
                if ($inputArguments['id'] !== 1337) {
                    return ['required'];
                }
                return [];
            }
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

### Resolve method

The resolve method is used in both queries and mutations and it's here that responses are created.

The first three parameters to the resolve method are hard-coded:

1. The `$root` object this resolve method belongs to (can be `null`)
2. The arguments passed as `array $args` (can be an empty array)
3. The query specific GraphQL context, can be customized by overriding `\Rebing\GraphQL\GraphQLController::queryContext`

Arguments here after will be attempted to be injected, similar to how controller methods works in Laravel.

You can typehint any class that you will need an instance of.

There are two hardcoded classes which depend on the local data for the query:
- `GraphQL\Type\Definition\ResolveInfo` has information useful for field resolution process.
- `Rebing\GraphQL\Support\SelectFields` allows eager loading of related models, see [Eager loading relationships](#eager-loading-relationships).

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
        'name' => 'User query'
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('user'));
    }

    public function args(): array
    {
        return [
            'id' => ['name' => 'id', 'type' => Type::string()]
        ];
    }

    public function resolve($root, $args, $context, ResolveInfo $info, SelectFields $fields, SomeClassThatDoLogging $logging)
    {
        $logging->log('fetched user');

        $select = $fields->getSelect();
        $with = $fields->getRelations();

        $users = User::select($select)->with($with);

        return $users->get();
    }
}
```

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

    protected function resolve($root, $args): ?string
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

The class can be instanciated by typehinting `SelectFields $selectField` in your resolve method.

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

    public function resolve($root, $args, $context, ResolveInfo $info, Closure $getSelectFields)
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

### Scalar types

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
        'name' => 'UserInput',
        'description' => 'A review with a comment and a score (0 to 5)'
    ];

    public function fields(): array
    {
        return [
            'firstName' => [
                'alias' => 'first_name',
                'description' => 'A comment (250 max chars)',
                'type' => Type::string(),
                'rules' => ['max:250']
            ],
            'lastName' => [
                'alias' => 'last_name',
                'description' => 'A score (0 to 5)',
                'type' => Type::int(),
                'rules' => ['min:0', 'max:5']
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
        'name' => 'UpdateUser'
    ];

    public function type(): Type
    {
        return GraphQL::type('user');
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

    public function resolve($root, $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
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

### Field deprecation

Sometimes you would want to deprecate a field but still have to maintain backward compatibility
until clients completely stop using that field. You can deprecate a field using
[directive](https://www.apollographql.com/docs/graphql-tools/schema-directives.html). If you add `deprecationReason`
to field attributes it will become marked as deprecated in GraphQL documentation. You can validate schema on client
using [Apollo Engine](https://blog.apollographql.com/schema-validation-with-apollo-engine-4032456425ba).


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

public function resolve($root, $args)
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
