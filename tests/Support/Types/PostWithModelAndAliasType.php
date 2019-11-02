<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Support\Types;

use DB;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Carbon;
use Rebing\GraphQL\Support\Type as GraphQLType;
use Rebing\GraphQL\Tests\Support\Models\Post;

class PostWithModelAndAliasType extends GraphQLType
{
    protected $attributes = [
        'name' => 'PostWithModelAndAlias',
        'model' => Post::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::id()),
            ],
            'description' => [
                'type' => Type::nonNull(Type::string()),
                'alias' => 'title',
            ],
            'commentCount' => [
                'type' => Type::nonNull(Type::int()),
                'alias' => DB::raw('(SELECT count(*) FROM comments WHERE posts.id = comments.post_id) AS commentCount'),
            ],

            'commentsLastMonth' => [
                'type' => Type::nonNull(Type::int()),
                'alias' => function () {
                    $day = Carbon::now()
                        ->startOfMonth()
                        ->format('Y-m-d H:i:s');

                    return DB::raw("(SELECT count(*) FROM comments WHERE posts.id = comments.post_id AND DATE(created_at) > '$day') AS commentsLastMonth");
                },
            ],

        ];
    }
}
