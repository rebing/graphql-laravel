<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\UnionMorphTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;
use Rebing\GraphQL\Tests\Support\Models\File;

class FileType extends GraphQLType
{
    protected $attributes = [
        'name' => 'File',
        'model' => File::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::id()),
            ],
            'name' => [
                'type' => Type::string(),
            ],
            'path' => [
                'type' => Type::string(),
            ],
            'folder' => [
                'type' => GraphQL::type('Folder'),
            ],
        ];
    }
}
