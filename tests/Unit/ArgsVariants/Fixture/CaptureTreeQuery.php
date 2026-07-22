<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ArgsVariants\Fixture;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type as GraphqlType;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class CaptureTreeQuery extends Query
{
    /** @var array<string,mixed>|null */
    public static ?array $tree = null;
    public static ?ResolveInfo $info = null;

    /** @var array<string,string> */
    protected $attributes = [
        'name' => 'captureTree',
    ];

    public function type(): GraphqlType
    {
        return Type::listOf(GraphQL::type('VariantPost'));
    }

    public function validateFieldArguments(array $fieldsAndArgumentsSelection): void
    {
        self::$tree = $fieldsAndArgumentsSelection;
        parent::validateFieldArguments($fieldsAndArgumentsSelection);
    }

    /**
     * @param mixed $root
     * @param array<string,mixed> $args
     * @param mixed $ctx
     * @return array<int,mixed>
     */
    public function resolve($root, array $args, $ctx, ResolveInfo $info): array
    {
        self::$info = $info;

        return [];
    }
}
