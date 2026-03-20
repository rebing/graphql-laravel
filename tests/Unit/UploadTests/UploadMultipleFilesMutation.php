<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\UploadTests;

use GraphQL\Type\Definition\Type;
use Illuminate\Http\Testing\File;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

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
                'type' => Type::listOf(GraphQL::type('Upload')),
            ],
        ];
    }

    /**
     * @param array<string,mixed> $args
     * @return array<int,string>
     */
    public function resolve(mixed $root, array $args): array
    {
        return array_map(
            static function (File $file): string {
                return \Safe\file_get_contents($file->getPathname());
            },
            $args['files'],
        );
    }
}
