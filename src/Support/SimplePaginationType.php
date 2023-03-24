<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support;

use GraphQL\Type\Definition\Type as GraphQLType;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class SimplePaginationType extends AbstractPaginationType
{
    protected function paginationTypeName(string $typeName, string $customName = null): string
    {
        return $customName ?: $typeName . 'SimplePagination';
    }

    /**
     * @return array<string, array<string,mixed>>
     */
    public function getPaginationFields(): array
    {
        return [
            'data' => [
                'type' => GraphQLType::nonNull(GraphQLType::listOf(GraphQLType::nonNull($this->underlyingType()))),
                'description' => 'List of items on the current page',
                'resolve' => function (Paginator $data): Collection {
                    return $data->getCollection();
                },
            ],
            'per_page' => [
                'type' => GraphQLType::nonNull(GraphQLType::int()),
                'description' => 'Number of items returned per page',
                'resolve' => function (Paginator $data): int {
                    return $data->perPage();
                },
                'selectable' => false,
            ],
            'current_page' => [
                'type' => GraphQLType::nonNull(GraphQLType::int()),
                'description' => 'Current page of the cursor',
                'resolve' => function (Paginator $data): int {
                    return $data->currentPage();
                },
                'selectable' => false,
            ],
            'from' => [
                'type' => GraphQLType::int(),
                'description' => 'Number of the first item returned',
                'resolve' => function (Paginator $data): ?int {
                    return $data->firstItem();
                },
                'selectable' => false,
            ],
            'to' => [
                'type' => GraphQLType::int(),
                'description' => 'Number of the last item returned',
                'resolve' => function (Paginator $data): ?int {
                    return $data->lastItem();
                },
                'selectable' => false,
            ],
            'has_more_pages' => [
                'type' => GraphQLType::nonNull(GraphQLType::boolean()),
                'description' => 'Determines if cursor has more pages after the current page',
                'resolve' => function (Paginator $data): bool {
                    return $data->hasMorePages();
                },
                'selectable' => false,
            ],
        ];
    }
}
