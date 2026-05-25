<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\SimplePaginationTest;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Pagination\Paginator;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class ExamplesSimplePaginationQuery extends Query
{
    protected $attributes = [
        'name' => 'Examples with simple pagination',
    ];

    public function type(): Type
    {
        return GraphQL::simplePaginate('Example');
    }

    public function args(): array
    {
        return [
            'take' => [
                'type' => Type::nonNull(Type::int()),
            ],
            'page' => [
                'type' => Type::nonNull(Type::int()),
            ],
        ];
    }

    /**
     * @param array<string,mixed> $args
     */
    public function resolve(mixed $root, array $args, mixed $context, ResolveInfo $resolveInfo): Paginator
    {
        /** @var array<int,array{test:string}> $data */
        $data = include __DIR__ . '/../../Support/Objects/data.php';

        $take = $args['take'];
        $pageIndex = $args['page'] - 1;

        // Laravel's simple paginator over-fetches by 1 to detect "has more pages"
        $items = collect($data)->slice($pageIndex * $take, $take + 1);

        return new Paginator(
            $items,
            $take,
            $args['page'],
        );
    }
}
