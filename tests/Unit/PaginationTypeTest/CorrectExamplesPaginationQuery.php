<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\PaginationTypeTest;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Pagination\LengthAwarePaginator;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

/**
 * Returns a correctly constructed `LengthAwarePaginator` for the test data
 * in tests/Support/Objects/data.php.
 *
 * (The shared fixture `Rebing\GraphQL\Tests\Support\Objects\ExamplesPaginationQuery`
 * has an off-by-one bug in `currentPage` semantics; we use a fresh fixture here
 * to avoid asserting against incorrect behaviour.)
 */
class CorrectExamplesPaginationQuery extends Query
{
    protected $attributes = [
        'name' => 'Examples with pagination (correct)',
    ];

    public function type(): Type
    {
        return GraphQL::paginate('Example');
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
    public function resolve(mixed $root, array $args, mixed $context, ResolveInfo $resolveInfo): LengthAwarePaginator
    {
        /** @var array<int,array{test:string}> $data */
        $data = include __DIR__ . '/../../Support/Objects/data.php';

        $take = $args['take'];
        $page = $args['page'];

        return new LengthAwarePaginator(
            items: collect($data)->slice(($page - 1) * $take, $take)->values(),
            total: \count($data),
            perPage: $take,
            currentPage: $page,
        );
    }
}
