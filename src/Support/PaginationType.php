<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support;

use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\NullableType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Rebing\GraphQL\Support\Facades\GraphQL;

class PaginationType extends ObjectType
{
    public function __construct(string $typeName, ?string $customName = null)
    {
        $name = $customName ?: $typeName . 'Pagination';

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
     * @phpstan-param (NullableType&GraphQLType)|NonNull $underlyingType
     *
     * @return array<string, array<string, mixed>>
     */
    protected function getPaginationFields(GraphQLType $underlyingType): array
    {
        return [
            'data' => [
                'type' => GraphQLType::nonNull(GraphQLType::listOf(GraphQLType::nonNull($underlyingType))),
                'description' => 'List of items on the current page',
                'resolve' => function (LengthAwarePaginator $data): Collection {
                    return $data->getCollection();
                },
            ],
            'total' => [
                'type' => GraphQLType::nonNull(GraphQLType::int()),
                'description' => 'Number of total items selected by the query',
                'resolve' => function (LengthAwarePaginator $data): int {
                    return $data->total();
                },
            ],
            'per_page' => [
                'type' => GraphQLType::nonNull(GraphQLType::int()),
                'description' => 'Number of items returned per page',
                'resolve' => function (LengthAwarePaginator $data): int {
                    return $data->perPage();
                },
            ],
            'current_page' => [
                'type' => GraphQLType::nonNull(GraphQLType::int()),
                'description' => 'Current page of the cursor',
                'resolve' => function (LengthAwarePaginator $data): int {
                    return $data->currentPage();
                },
            ],
            'from' => [
                'type' => GraphQLType::int(),
                'description' => 'Number of the first item returned',
                'resolve' => function (LengthAwarePaginator $data): ?int {
                    return $data->firstItem();
                },
            ],
            'to' => [
                'type' => GraphQLType::int(),
                'description' => 'Number of the last item returned',
                'resolve' => function (LengthAwarePaginator $data): ?int {
                    return $data->lastItem();
                },
            ],
            'last_page' => [
                'type' => GraphQLType::nonNull(GraphQLType::int()),
                'description' => 'The last page (number of pages)',
                'resolve' => function (LengthAwarePaginator $data): int {
                    return $data->lastPage();
                },
            ],
            'has_more_pages' => [
                'type' => GraphQLType::nonNull(GraphQLType::boolean()),
                'description' => 'Determines if cursor has more pages after the current page',
                'resolve' => function (LengthAwarePaginator $data): bool {
                    return $data->hasMorePages();
                },
            ],
        ];
    }
}
