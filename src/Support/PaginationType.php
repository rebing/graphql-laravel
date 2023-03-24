<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Rebing\GraphQL\Support\Facades\GraphQL;

class PaginationType extends ObjectType
{
    public function __construct(string $typeName, string $customName = null)
    {
        $name = $customName ?: $typeName . 'Pagination';

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
                'resolve' => fn (LengthAwarePaginator $data): Collection => $data->getCollection(),
            ],
            'total' => [
                'type' => GraphQLType::nonNull(GraphQLType::int()),
                'description' => 'Number of total items selected by the query',
                'resolve' => fn (LengthAwarePaginator $data): int => $data->total(),
                'selectable' => false,
            ],
            'per_page' => [
                'type' => GraphQLType::nonNull(GraphQLType::int()),
                'description' => 'Number of items returned per page',
                'resolve' => fn (LengthAwarePaginator $data): int => $data->perPage(),
                'selectable' => false,
            ],
            'current_page' => [
                'type' => GraphQLType::nonNull(GraphQLType::int()),
                'description' => 'Current page of the cursor',
                'resolve' => fn (LengthAwarePaginator $data): int => $data->currentPage(),
                'selectable' => false,
            ],
            'from' => [
                'type' => GraphQLType::int(),
                'description' => 'Number of the first item returned',
                'resolve' => function (LengthAwarePaginator $data): ?int {
                    return $data->firstItem();
                },
                'selectable' => false,
            ],
            'to' => [
                'type' => GraphQLType::int(),
                'description' => 'Number of the last item returned',
                'resolve' => function (LengthAwarePaginator $data): ?int {
                    return $data->lastItem();
                },
                'selectable' => false,
            ],
            'last_page' => [
                'type' => GraphQLType::nonNull(GraphQLType::int()),
                'description' => 'The last page (number of pages)',
                'resolve' => function (LengthAwarePaginator $data): int {
                    return $data->lastPage();
                },
                'selectable' => false,
            ],
            'has_more_pages' => [
                'type' => GraphQLType::nonNull(GraphQLType::boolean()),
                'description' => 'Determines if cursor has more pages after the current page',
                'resolve' => function (LengthAwarePaginator $data): bool {
                    return $data->hasMorePages();
                },
                'selectable' => false,
            ],
        ];
    }
}
