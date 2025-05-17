<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Collection;
use Rebing\GraphQL\Support\Facades\GraphQL;

class CursorPaginationType extends ObjectType
{
    public function __construct(string $typeName, ?string $customName = null)
    {
        $name = $customName ?: $typeName . 'CursorPagination';

        $underlyingType = GraphQL::type($typeName);

        $config = [
            'name' => $name,
            'fields' => $this->getPaginationFields($underlyingType),
        ];

        if (isset($underlyingType->config['model'])) {
            $config['model'] = $underlyingType->config['model'];
        }

        parent::__construct($config);
    }

    /**
     * @return array<string, array<string,mixed>>
     */
    protected function getPaginationFields(GraphQLType $underlyingType): array
    {
        return [
            'data' => [
                'type' => GraphQLType::nonNull(GraphQLType::listOf(GraphQLType::nonNull($underlyingType))),
                'description' => 'List of items on the current page',
                'resolve' => function (CursorPaginator $data): Collection {
                    return $data->getCollection();
                },
            ],
            'per_page' => [
                'type' => GraphQLType::nonNull(GraphQLType::int()),
                'description' => 'Number of items returned per page',
                'resolve' => function (CursorPaginator $data): int {
                    return $data->perPage();
                },
                'selectable' => false,
            ],
            'previous_cursor' => [
                'type' => GraphQLType::string(),
                'description' => 'Previous page cursor',
                'resolve' => function (CursorPaginator $data): ?string {
                    return $data->previousCursor()?->encode();
                },
                'selectable' => false,
            ],
            'next_cursor' => [
                'type' => GraphQLType::string(),
                'description' => 'Next page cursor',
                'resolve' => function (CursorPaginator $data): ?string {
                    return $data->nextCursor()?->encode();
                },
                'selectable' => false,
            ],
        ];
    }
}
