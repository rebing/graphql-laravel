<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\AliasArguments\Stubs;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InputType;

/**
 * Input type with circular reference to BookInputObject.
 * Used to test exponential time complexity fix in AliasArguments.
 *
 * This type has many fields to increase branching factor and demonstrate
 * exponential time complexity when combined with circular references.
 */
class AuthorInputObject extends InputType
{
    public const TYPE = 'AuthorInputObject';

    /** @var array<string,string> */
    protected $attributes = [
        'name' => self::TYPE,
    ];

    public function fields(): array
    {
        return [
            'name' => [
                'type' => Type::string(),
                'alias' => 'name_alias',
            ],
            'biography' => [
                'type' => Type::string(),
                'alias' => 'biography_alias',
            ],
            'country' => [
                'type' => Type::string(),
                'alias' => 'country_alias',
            ],
            'birthYear' => [
                'type' => Type::int(),
                'alias' => 'birth_year_alias',
            ],
            'website' => [
                'type' => Type::string(),
                'alias' => 'website_alias',
            ],
            // Circular references: Author -> Book -> Author (multiple paths)
            'books' => [
                'type' => Type::listOf(GraphQL::type(BookInputObject::TYPE)),
            ],
            'featuredBook' => [
                'type' => GraphQL::type(BookInputObject::TYPE),
            ],
            'latestBook' => [
                'type' => GraphQL::type(BookInputObject::TYPE),
            ],
        ];
    }
}
