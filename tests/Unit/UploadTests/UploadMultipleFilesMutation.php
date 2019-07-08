<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\UploadTests;

use GraphQL\Type\Definition\Type;
use Illuminate\Http\Testing\File;
use Rebing\GraphQL\Support\Mutation;
use Rebing\GraphQL\Support\UploadType;

class UploadMultipleFilesMutation extends Mutation
{
    protected $attributes = [
        'name' => 'uploadMultipleFiles',
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::listOf(Type::nonNull(Type::string())));
    }

    public function args(): array
    {
        return [
            'files' => [
                'type' => Type::listOf(UploadType::getInstance()),
            ],
        ];
    }

    public function resolve($root, $args): array
    {
        return array_map(
            function (File $file): string {
                return $file->get();
            },
            $args['files']
        );
    }
}
