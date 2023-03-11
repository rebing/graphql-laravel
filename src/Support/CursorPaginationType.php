<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Collection;
use Rebing\GraphQL\Support\Facades\GraphQL;

class CursorPaginationType extends ObjectType
{
    public function __construct(string $typeName, string $customName = null)
    {
        $name = $customName ?: $typeName . 'CursorPagination';

        $config = [
            'name' => $name,
            'fields' => $this->getPaginationFields($typeName),
        ];

        $underlyingType = GraphQL::type($typeName);

        if (isset($underlyingType->config['model'])) {
            $config['model'] = $underlyingType->config['model'];
        }

        parent::__construct($config);
    }

    protected function getPaginationFields(string $typeName): array
    {
        return [
            'data' => [
                'type' => GraphQLType::listOf(GraphQL::type($typeName)),
                'description' => 'List of items on the current page',
                'resolve' => function (CursorPaginator $data): Collection {
                    return $data->getCollection();
                },
            ],
            'path' => [
                'type' => GraphQLType::nonNull(GraphQLType::string()),
                'description' => 'Base path to assign to all URLs',
                'resolve' => function (CursorPaginator $data): string {
                    return $data->path();
                },
                'selectable' => false,
            ],
            'per_page' => [
                'type' => GraphQLType::nonNull(GraphQLType::string()),
                'description' => 'Number of items returned per page',
                'resolve' => function (CursorPaginator $data): int {
                    return $data->perPage();
                },
                'selectable' => false,
            ],
            'next_cursor' => [
                'type' => GraphQLType::string(),
                'description' => 'Get the cursor that points to the next set of items.',
                'resolve' => function (CursorPaginator $data): string|null {
                    return $data->nextCursor()?->encode();
                },
                'selectable' => false,
            ],
            'next_page_url' => [
                'type' => GraphQLType::string(),
                'description' => 'The URL for the next page, or null',
                'resolve' => function (CursorPaginator $data): string|null {
                    return $data->nextPageUrl();
                },
                'selectable' => false,
            ],
            'prev_cursor' => [
                'type' => GraphQLType::string(),
                'description' => 'Get the cursor that points to the previous set of items',
                'resolve' => function (CursorPaginator $data): string|null {
                    return $data->previousCursor()?->encode();
                },
                'selectable' => false,
            ],
            'prev_page_url' => [
                'type' => GraphQLType::string(),
                'description' => 'Get the URL for the previous page',
                'resolve' => function (CursorPaginator $data): string|null {
                    return $data->previousPageUrl();
                },
                'selectable' => false,
            ],
        ];
    }
}
