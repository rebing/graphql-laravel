# Laravel GraphQL

Core code is from [Folklore's laravel-graphql](https://github.com/Folkloreatelier/laravel-graphql)

Uses Facebook GraphQL with Laravel 5. It is based on the PHP implementation [here](https://github.com/webonyx/graphql-php). You can find more information about GraphQL in the [GraphQL Introduction](http://facebook.github.io/react/blog/2015/05/01/graphql-introduction.html) on the [React](http://facebook.github.io/react) blog or you can read the [GraphQL specifications](https://facebook.github.io/graphql/). This is a work in progress.

This package is compatible with Eloquent model (or any other data source). It allows creating **queries** and **mutations** as request endpoints.
Custom **middleware** can be defined for each query/mutation. Queries return **types**, which can have custom **privacy** settings.
The queried fields will have the option to be retrieved **dynamically** from the database with the help of the `SelectFields` class.

Can also generate a documentation of your API with `$ php artisan graphql:generate-doc`

## Installation

#### Dependencies:

* [Laravel 5.x](https://github.com/laravel/laravel)
* [GraphQL PHP](https://github.com/webonyx/graphql-php)


#### Installation:

**1-** Require the package via Composer in your `composer.json`.
```json
{
  "require": {
    "Rebing/graphql": "1.0.*"
  }
}
```

**2-** Run Composer to install or update the new requirement.

```bash
$ composer install
```

or

```bash
$ composer update
```

**3-** Add the service provider to your `app/config/app.php` file
```php
'Rebing\GraphQL\GraphQLServiceProvider',
```

**4-** Add the facade to your `app/config/app.php` file
```php
'GraphQL' => 'Rebing\GraphQL\Support\Facades\GraphQL',
```

**5-** Publish the configuration file

```bash
$ php artisan vendor:publish --provider="Rebing\GraphQL\GraphQLServiceProvider"
```

**6-** Review the configuration file

```
config/graphql.php
```

## Usage

- [Creating a query](#creating-a-query)
- [Creating a mutation](#creating-a-mutation)
- [Adding validation to mutation](#adding-validation-to-mutation)
- [Generate documentation](#generate-documentation)

##### Advanced Usage
- [Authorization](#authorization)
- [Privacy](#privacy)
- [Query variables](#query-variables)
- [Custom field](#custom-field)
- [Eager loading relationships](#eager-loading-relationships)
- [Type relationship query](#type-relationship-query)

### Creating a query

First you need to create a type. The Eloquent Model is only required, if specifying relations.

**NB! The `selectable` key is required, if it's a non-database field or not a relation**

```php

	namespace App\GraphQL\Type;
	
	use GraphQL\Type\Definition\Type;
	use Rebing\GraphQL\Support\Type as GraphQLType;
    
    class UserType extends GraphQLType {
        
        protected $attributes = [
            'name'          => 'User',
            'description'   => 'A user',
            'model'         => UserModel::class,
        ];
		
        public function fields()
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
                // Uses the 'scopeIsMe' function on our custom User model
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
		'user' => 'App\GraphQL\Type\UserType'
	]

```

You could also add the type with the `GraphQL` Facade, in a service provider for example.

```php
    
	GraphQL::addType('App\GraphQL\Type\UserType', 'user');

```

Then you need to define a query that returns this type (or a list). You can also specify arguments that you can use in the resolve method.
```php

	namespace App\GraphQL\Query;
	
	use GraphQL;
	use GraphQL\Type\Definition\Type;
	use Rebing\GraphQL\Support\Query;    
	use App\User;
	
	class UsersQuery extends Query {
	
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
			if(isset($args['id']))
			{
				return User::where('id' , $args['id'])->get();
			}
			else if(isset($args['email']))
			{
				return User::where('email', $args['email'])->get();
			}
			else
			{
				return User::all();
			}
		}
	
	}

```

Add the query to the `config/graphql.php` configuration file

```php
    
    'schema' => [
		'query' => [
			'users' => 'App\GraphQL\Query\UsersQuery'
		],
		// ...
	]

```

Or using the `GraphQL` facade

```php
    
    GraphQL::addQuery('App\GraphQL\Query\UsersQuery', 'users');

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

A mutation is like any other query, it accepts arguments (which will be used to do the mutation) and returns an object of a certain type.

For example a mutation to update the password of a user. First you need to define the Mutation.

```php

	namespace App\GraphQL\Mutation;
	
	use GraphQL;
	use GraphQL\Type\Definition\Type;
	use Rebing\GraphQL\Support\Mutation;    
	use App\User;
	
	class UpdateUserPasswordMutation extends Mutation {
	
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
			if(!$user)
			{
				return null;
			}
			
			$user->password = bcrypt($args['password']);
			$user->save();
			
			return $user;
		}
	
	}

```

As you can see in the `resolve` method, you use the arguments to update your model and return it.

You then add the muation to the `config/graphql.php` configuration file

```php
    
    'schema' => [
		'mutation' => [
			'updateUserPassword' => 'App\GraphQL\Mutation\UpdateUserPasswordMutation'
		],
		// ...
	]

```

Or using the `GraphQL` facade

```php
    
    GraphQL::addMutation('App\GraphQL\Mutation\UpdateUserPasswordMutation', 'updateUserPassword');

```

You should then be able to use the following query on your endpoint to do the mutation.

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

#### Adding validation to mutation

It is possible to add validation rules to mutation. It uses the laravel `Validator` to performs validation against the `args`.

When creating a mutation, you can add a method to define the validation rules that apply by doing the following:

```php

	namespace App\GraphQL\Mutation;
	
	use GraphQL;
	use GraphQL\Type\Definition\Type;
	use Rebing\GraphQL\Support\Mutation;    
	use App\User;
	
	class UpdateUserEmailMutation extends Mutation {
	
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
				'email' => ['name' => 'password', 'type' => Type::string()]
			];
		}
		
		public function rules()
		{
			return [
				'id' => ['required'],
				'email' => ['required', 'email']
			];
		}
		
		public function resolve($root, $args)
		{
			$user = User::find($args['id']);
			if(!$user)
			{
				return null;
			}
			
			$user->email = $args['email'];
			$user->save();
			
			return $user;
		}
	
	}

```

Alternatively you can define rules with each args

```php
	
	class UpdateUserEmailMutation extends Mutation {
	
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
					'name' => 'password',
					'type' => Type::string(),
					'rules' => ['required', 'email']
				]
			];
		}
		
		//...
	
	}

```

When you execute a mutation, it will return the validation errors. Since GraphQL specifications define a certain format for errors, the validation errors messages are added to the error object as a extra `validation` attribute. To find the validation error, you should check for the error with a `message` equals to `'validation'`, then the `validation` attribute will contain the normal errors messages returned by the Laravel Validator.

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

### Generating documentation

Once you have created your queries, mutation, types and modified the configuration, run `$ php artisan grapqhl:generate-doc`. This will dynamically
generate a documentation file of your current graph API in your root folder.

[Check out an example doc](src/example/GraphQL-doc.md)

## Advanced usage

### Authorization

For authorization similar to Laravel's Request (or middleware) functionality, we can override the `authorize()` function in a Query or Mutation.
An example of Laravel's `'auth'` middleware:

```
use Auth;

class UsersQuery extends Query
{
    public function authorize(array $args)
    {
        // true, if logged in
        return ! Auth::guest();
    }
    
    ...
}
```

Or we can make use of arguments passed via the graph query:

```
use Auth;

class UsersQuery extends Query
{
    public function authorize(array $args)
    {
        if(isset($args['id']))
        {
            return Auth::id() == $args['id'];
        }
        
        return true;
    }
    
    ...
}
```

### Privacy

You can set custom privacy attributes for every Type's Field. If a field is not allowed, `null` will be returned. For example, if you want the user's email to only be accessible to themselves:

```
class UserType extends GraphQLType {
        
        ...
		
        public function fields()
        {
            return [
                'id' => [
                    'type'          => Type::nonNull(Type::string()),
                    'description'   => 'The id of the user'
                ],
                'email' => [
                    'type'          => Type::string(),
                    'description'   => 'The email of user',
                    'privacy'       => function(array $args)
                    {
                        return $args['id'] == Auth::id();
                    }
                ]
            ];
        }
            
        ...
        
    }
```

or you can create a class that extends the abstract GraphQL Privacy class:

```
use Rebing\GraphQL\Support\Privacy;
use Auth;

class MePrivacy extends Privacy {

    public function validate(array $args)
    {
        return $args['id'] == Auth::id();
    }

}
```

```
use MePrivacy;

class UserType extends GraphQLType {
        
        ...
		
        public function fields()
        {
            return [
                'id' => [
                    'type'          => Type::nonNull(Type::string()),
                    'description'   => 'The id of the user'
                ],
                'email' => [
                    'type'          => Type::string(),
                    'description'   => 'The email of user',
                    'privacy'       => MePrivacy::validate(),
                ]
            ];
        }
            
        ...
        
    }
```

### Query Variables

GraphQL offers you the possibility to use variables in your query so you don't need to "hardcode" value. This is done like that:

```
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

### Custom field

You can also define a field as a class if you want to reuse it in multiple types.

```php

namespace App\GraphQL\Fields;
	
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Field;

class PictureField extends Field {
        
        protected $attributes = [
            'description'   => 'A picture',
        ];
	
	public function type()
	{
		return Type::string();
	}
		
	public function args()
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

namespace App\GraphQL\Type;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

use App\GraphQL\Fields\PictureField;

class UserType extends GraphQLType {
        
        protected $attributes = [
            'name'          => 'User',
            'description'   => 'A user',
            'model'         => UserModel::class,
        ];
	
	public function fields()
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

The third argument passed to a query's resolve method is an instance of `Rebing\GraphQL\Support\SelectFields` which you can use to retrieve keys from the request. The following is an example of using this information to eager load related Eloquent models.
This way only the required fields will be queried from the database.

Your Query would look like

```php
	namespace App\GraphQL\Query;
	
	use GraphQL;
	use GraphQL\Type\Definition\Type;
	use GraphQL\Type\Definition\ResolveInfo;
	use Rebing\GraphQL\Support\SelectFields;
	use Rebing\GraphQL\Support\Query;
	
	use App\User;

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
        
		public function resolve($root, $args, SelectFields $fields, ResolveInfo $info)
		{
		    // $info->getFieldSelection($depth = 3);
		    
			$select = $fields->getSelect();
			$with = $fields->getRelations();
			
			$users = User::select($select)->with($with);
			
			return $users->get();
		}
	}
```

Your Type for User would look like. The `profile` and `posts` relations must also exist in the UserModel's relations.
If some fields are required for the relation to load or validation etc, then you can define an `always` attribute that will add the given attributes to select.

```php
<?php

namespace App\GraphQL\Type;

use Rebing\GraphQL\Support\Facades\GraphQL;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class UserType extends GraphQLType
{
    /**
     * @var array
     */
    protected $attributes = [
        'name'          => 'User',
        'description'   => 'A user',
        'model'         => UserModel::class,
    ];

    /**
     * @return array
     */
    public function fields()
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

    public function fields()
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

    public function fields()
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

```
class UserType extends GraphQLType {

    ...
    
    public function fields()
    {
        return [
            
            ...
            
            // Relation
            'posts' => [
                'type'          => Type::listOf(GraphQL::type('post')),
                'description'   => 'A list of posts written by the user',
                // The first args are the parameters passed to the query
                'query'         => function(array $args, $query) {
                    return $query->where('posts.created_at', '>', $args['date_from']);
                }
            ]
        ];
    }

}
```


Lastly your query would look like, if using Homestead

For example, if you use homestead:

```
http://homestead.app/graphql?query=query+FetchUsers{users{uuid, email, team{name}}}
```