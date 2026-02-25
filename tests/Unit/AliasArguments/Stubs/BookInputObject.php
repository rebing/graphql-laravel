<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\AliasArguments\Stubs;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InputType;

/**
 * Input type with circular reference to AuthorInputObject.
 * Used to test exponential time complexity fix in AliasArguments.
 *
 * This type has many fields to increase branching factor and demonstrate
 * exponential time complexity when combined with circular references.
 */
class BookInputObject extends InputType
{
    public const TYPE = 'BookInputObject';

    /** @var array<string,string> */
    protected $attributes = [
        'name' => self::TYPE,
    ];

    public function fields(): array
    {
        return [
            'title' => [
                'type' => Type::string(),
                'alias' => 'title_alias',
            ],
            'isbn' => [
                'type' => Type::string(),
                'alias' => 'isbn_alias',
            ],
            'description' => [
                'type' => Type::string(),
                'alias' => 'description_alias',
            ],
            'publishYear' => [
                'type' => Type::int(),
                'alias' => 'publish_year_alias',
            ],
            'pageCount' => [
                'type' => Type::int(),
                'alias' => 'page_count_alias',
            ],
            // Circular references: Book -> Author -> Book (multiple paths)
            'authors' => [
                'type' => Type::listOf(GraphQL::type(AuthorInputObject::TYPE)),
            ],
            'primaryAuthor' => [
                'type' => GraphQL::type(AuthorInputObject::TYPE),
            ],
            'editor' => [
                'type' => GraphQL::type(AuthorInputObject::TYPE),
            ],
        ];
    }
}
