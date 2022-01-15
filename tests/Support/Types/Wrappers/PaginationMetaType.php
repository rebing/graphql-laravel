<?php

namespace Rebing\GraphQL\Tests\Support\Types\Wrappers;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Rebing\GraphQL\Support\Facades\GraphQL;

class PaginationMetaType extends ObjectType
{
    public function __construct(string $typeName, string $customName = null)
    {
        $config = [
            'name' => $customName,
            'fields' => $this->getPaginationMetaFields($typeName),
        ];

        $underlyingType = GraphQL::type($typeName);
        if (isset($underlyingType->config['model'])) {
            $config['model'] = $underlyingType->config['model'];
        }

        parent::__construct($config);
    }

    protected function getPaginationMetaFields(string $typeName): array
    {
        return [
            'total' => [
                'type' => Type::nonNull(Type::int()),
                'resolve' => function (LengthAwarePaginator $data): int {
                    return $data->total();
                },
                'selectable' => false,
            ],
            'per_page' => [
                'type' => Type::nonNull(Type::int()),
                'resolve' => function (LengthAwarePaginator $data): int {
                    return $data->perPage();
                },
                'selectable' => false,
            ],
            'current_page' => [
                'type' => Type::nonNull(Type::int()),
                'resolve' => function (LengthAwarePaginator $data): int {
                    return $data->currentPage();
                },
                'selectable' => false,
            ],
            'from' => [
                'type' => Type::int(),
                'resolve' => function (LengthAwarePaginator $data): ?int {
                    return $data->firstItem();
                },
                'selectable' => false,
            ],
            'to' => [
                'type' => Type::int(),
                'resolve' => function (LengthAwarePaginator $data): ?int {
                    return $data->lastItem();
                },
                'selectable' => false,
            ],
            'last_page' => [
                'type' => Type::nonNull(Type::int()),
                'resolve' => function (LengthAwarePaginator $data): int {
                    return $data->lastPage();
                },
                'selectable' => false,
            ],
            'has_more_pages' => [
                'type' => Type::nonNull(Type::boolean()),
                'resolve' => function (LengthAwarePaginator $data): bool {
                    return $data->hasMorePages();
                },
                'selectable' => false,
            ],
        ];
    }
}
