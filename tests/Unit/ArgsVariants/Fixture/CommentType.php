<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ArgsVariants\Fixture;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class CommentType extends GraphQLType
{
    /** @var array<string,string> */
    protected $attributes = [
        'name' => 'VariantComment',
    ];

    /**
     * @return array<string,mixed>
     */
    public function fields(): array
    {
        return [
            'id' => ['type' => Type::nonNull(Type::int())],
            'body' => ['type' => Type::string()],
        ];
    }
}
