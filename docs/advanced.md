# Advanced Usage

- [Authorization](#authorization)
- [Privacy](#privacy)
- [Query variables](#query-variables)
- [Custom field](#custom-field)
- [Eager loading relationships](#eager-loading-relationships)
- [Type relationship query](#type-relationship-query)
- [Pagination](#pagination)
- [Batching](#batching)
- [Enums](#enums)
- [Unions](#unions)
- [Interfaces](#interfaces)
- [Input Object](#input-object)

### Authorization

For authorization similar to Laravel's Request (or middleware) functionality, we can override the `authorize()` function in a Query or Mutation.
An example of Laravel's `'auth'` middleware:

```php
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

```php
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

```php
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

```php
use Rebing\GraphQL\Support\Privacy;
use Auth;

class MePrivacy extends Privacy {

    public function validate(array $args)
    {
        return $args['id'] == Auth::id();
    }

}
```

```php
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
                    'privacy'       => MePrivacy::class,
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

Notice that your client side framework might use another parameter name than `params`. You can customize the parameter name to anything your client is using by adjusting the `params_key` in the `graphql.php` configuration file.

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

```php
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

### Pagination

Pagination will be used, if a query or mutation returns a `PaginationType`. Note that you have to manually handle the 
limit and page values:

```php
class PostsQuery extends Query {

    public function type()
    {
        return GraphQL::paginate('posts');
    }
    
    ...
    
    public function resolve($root, $args, SelectFields $fields)
    {
        return Post::with($fields->getRelations())->select($fields->getSelect())
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

#### Customising the pagination results
You can add in additional metadata results alongside the Laravel 'standard' ones. To keep the Posts theme going, we could 
create some additional metadata to show the total number of posts, comments and likes for the posts returned in the paginated 
results.

First, create a class that returns the custom fields you want to see:

```
use Illuminate\Pagination\LengthAwarePaginator;
use GraphQL\Type\Definition\Type as GraphQLType;

class MyCustomPaginationFields
{
    public static function getPaginationFields()
    {
        return [
            // Pass through a User object that we can use to calculate the totals
            'totals_for_user' => [
                'type'          => \GraphQL::type('total'),
                'description'   => 'Total posts, comments and likes for the result set',
                'resolve'       => function () {
                    return app()->make('App\User');
                },
                'selectable'    => false,
            ],
            // Add in the 'last page' value from the Laravel Paginator
            'last_page' => [
                'type'          => GraphQLType::nonNull(GraphQLType::int()),
                'description'   => 'Last page of the result set',
                'resolve'       => function (LengthAwarePaginator $data) {
                    return $data->lastPage();
                },
                'selectable'    => false,
            ],
        ];
    }
}
```

Then add a config entry to map this class:

```
'custom_paginators' => [
    'post_pagination' => \Namespace\Of\The\MyCustomPaginationFields::class,
],
```
You can now query against the new fields in the same way as for the core pagination metadata. We could now extend the example 
query from earlier to get the new fields.

Query: `posts(limit:10,page:1){data{id},totals_for_user,total,per_page,last_page}`:

```
{
    "data": {
        "posts: [
            "data": [
                {"id": 3},
                {"id": 5},
                ...
            ],
            "totals_for_user": [
                {"posts": 12},
                {"comments": 42},
                {"likes": 101}
            ],
            "total": 21,
            "per_page": 10,
            "last_page": 3
        ]
    }
}
```

 
If you want to change the name of a default field to fit with users expectations (maybe you want 'total_records' rather 
than 'total'), just copy the entry for the field you want to replace (they're in Rebing/GraphQL/Support/PaginationType.php) 
and add it to your custom class.


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


### Enums

Enumeration types are a special kind of scalar that is restricted to a particular set of allowed values.
Read more about Enums [here](http://graphql.org/learn/schema/#enumeration-types)

First create an Enum as an extension of the GraphQLType class:
```php
// app/GraphQL/Enums/EpisodeEnum.php
namespace App\GraphQL\Enums;

use Rebing\GraphQL\Support\Type as GraphQLType;

class EpisodeEnum extends GraphQLType {

    protected $enumObject = true;

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
Register the Enum in the 'types' array of the graphql.php config file:

```php
// config/graphql.php
'types' => [
    'EpisodeEnum' => EpisodeEnum::class
];
```

Then use it like:
```php
// app/GraphQL/Type/TestType.php
class TestType extends GraphQLType {

   public function fields()
   {
        return [
            'episode_type' => [
                'type' => GraphQL::type('EpisodeEnum')
            ]
        ]
   }
   
}
```


### Unions

A Union is an abstract type that simply enumerates other Object Types. The value of Union Type is actually a value of one of included Object Types.

It's useful if you need to return unrelated types in the same Query. For example when implementing a search for multiple different entities.

Example for defining a UnionType:

```php
// app/GraphQL/Unions/SearchResultUnion.php
namespace App\GraphQL\Unions;

use Rebing\GraphQL\Support\UnionType;

class SearchResultUnion extends UnionType {

    protected $attributes = [
        'name' => 'SearchResult',
    ];

    public function types()
    {
        return [
            \GraphQL::type('Post'),
            \GraphQL::type('Episode'),
        ];
    }

    public function resolveType($value)
    {
        if ($value instanceof Post) {
            return \GraphQL::type('Post');
        } elseif ($value instanceof Episode) {
            return \GraphQL::type('Episode');
        }
    }
}

```


### Interfaces

You can use interfaces to abstract a set of fields. Read more about Interfaces [here](http://graphql.org/learn/schema/#interfaces)

An implementation of an interface:

```php
<?php
// app/GraphQL/Interfaces/CharacterInterface.php
namespace App\GraphQL\Interfaces;

use GraphQL;
use Rebing\GraphQL\Support\InterfaceType;
use GraphQL\Type\Definition\Type;

class CharacterInterface extends InterfaceType {
    protected $attributes = [
        'name' => 'Character',
        'description' => 'Character interface.',
    ];

    public function fields()
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
        } else if  ($type === 'droid') {
            return GraphQL::type('Droid');
        }
    }
}
```

A Type that implements an interface:

```php
<?php
// app/GraphQL/Types/HumanType.php
namespace App\GraphQL\Types;

use GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;
use GraphQL\Type\Definition\Type;

class HumanType extends GraphQLType {

    protected $attributes = [
        'name' => 'Human',
        'description' => 'A human.'
    ];

    public function fields()
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

    public function interfaces()
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
public function fields()
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
public function fields()
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
// app/GraphQL/Enums/EpisodeEnum.php
namespace App\GraphQL\InputObject;

use Rebing\GraphQL\Support\Type as GraphQLType;

class ReviewInput extends GraphQLType {

    protected $inputObject = true;

    protected $attributes = [
        'name' => 'ReviewInput',
        'description' => 'A review with a comment and a score (0 to 5)'
    ];

    public function fields()
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
                'description' => 'A score (0 to 5)'
                'type' => Type::int(),
                'rules' => ['min:0', 'max:5']
            ]
        ];
    }
}

```
Register the Input Object in the 'types' array of the graphql.php config file:

```php
// config/graphql.php
'types' => [
    'ReviewInput' => ReviewInput::class
];
```

Then use it in a mutation, like:
```php
// app/GraphQL/Type/TestMutation.php
class TestMutation extends GraphQLType {

   public function args()
   {
        return [
            'review' => [
                'type' => GraphQL::type('ReviewInput')
            ]
        ]
   }
   
}
```
