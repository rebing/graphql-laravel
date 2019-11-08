<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\UploadTests;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class UploadSingleFileMutation extends Mutation
{
    protected $attributes = [
        'name' => 'uploadSingleFile',
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::string());
    }

    public function args(): array
    {
        return [
            'file' => [
                'type' => GraphQL::type('Upload'),
            ],
        ];
    }

    public function resolve($root, $args): string
    {
        return file_get_contents($args['file']->getPathname());
    }
}
