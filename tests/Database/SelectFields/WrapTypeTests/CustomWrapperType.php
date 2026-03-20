<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\WrapTypeTests;

use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\NullableType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Rebing\GraphQL\Support\Contracts\WrapType;
use Rebing\GraphQL\Support\Facades\GraphQL;

class CustomWrapperType extends ObjectType implements WrapType
{
    public function __construct(string $typeName, ?string $customName = null)
    {
        $name = $customName ?: $typeName . 'Wrapped';

        $underlyingType = GraphQL::type($typeName);

        $config = [
            'name' => $name,
            'fields' => $this->getWrapperFields($underlyingType),
        ];

        if (isset($underlyingType->config['model'])) {
            $config['model'] = $underlyingType->config['model'];
        }

        parent::__construct($config);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function getWrapperFields((NullableType&GraphQLType)|NonNull $underlyingType): array
    {
        return [
            'data' => [
                'type' => GraphQLType::nonNull(GraphQLType::listOf(GraphQLType::nonNull($underlyingType))),
                'description' => 'List of items on the current page',
                'resolve' => function (LengthAwarePaginator $data): Collection {
                    return $data->getCollection();
                },
            ],
            'message' => [
                'type' => GraphQLType::string(),
                'description' => 'A status message',
                'selectable' => false,
                'resolve' => function (): string {
                    return 'OK';
                },
            ],
        ];
    }
}
