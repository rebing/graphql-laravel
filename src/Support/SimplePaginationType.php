<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Rebing\GraphQL\Support\Facades\GraphQL;

class SimplePaginationType extends ObjectType
{
    public function __construct(string $typeName, string $customName = null)
    {
        $name = $customName ?: $typeName . 'SimplePagination';

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

    /**
     * @return array<string, array<string,mixed>>
     */
    protected function getPaginationFields(string $typeName): array
    {
        return [
            'data' => [
                'type' => GraphQLType::listOf(GraphQL::type($typeName)),
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
