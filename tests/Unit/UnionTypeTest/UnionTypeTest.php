<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\UnionTypeTest;

use GraphQL\Type\Definition\UnionType as BaseUnionType;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Tests\TestCase;

/**
 * Covers `Rebing\GraphQL\Support\UnionType` — both the `getAttributes()` /
 * `toType()` methods directly and the type's behaviour as part of an actual
 * GraphQL query.
 *
 * The shared fixture `tests/Support/Objects/ExampleUnionType.php` exists but
 * is never registered in any schema, so all `UnionType` paths were previously
 * uncovered.
 */
class UnionTypeTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('graphql.schemas.default', [
            'query' => [
                AnimalsQuery::class,
            ],
        ]);

        $app['config']->set('graphql.types', [
            CatType::class,
            DogType::class,
            AnimalUnionType::class,
        ]);
    }

    public function testGetAttributesIncludesTypesAndResolveType(): void
    {
        $type = new AnimalUnionType;
        $attributes = $type->getAttributes();

        self::assertArrayHasKey('types', $attributes);
        self::assertCount(2, $attributes['types']);
        self::assertArrayHasKey('resolveType', $attributes);
        self::assertIsCallable($attributes['resolveType']);
    }

    public function testToTypeReturnsBaseUnionType(): void
    {
        $type = new AnimalUnionType;
        $unionType = $type->toType();

        self::assertInstanceOf(BaseUnionType::class, $unionType);
        self::assertSame('Animal', $unionType->name());
    }

    public function testQueryResolvesPolymorphicallyViaUnion(): void
    {
        $query = <<<'GRAPHQL'
{
  animals {
    __typename
    ... on Cat { name meow_volume }
    ... on Dog { name good_boy }
  }
}
GRAPHQL;

        $result = $this->httpGraphql($query);

        self::assertSame([
            'data' => [
                'animals' => [
                    [
                        '__typename' => 'Cat',
                        'name' => 'Whiskers',
                        'meow_volume' => 7,
                    ],
                    [
                        '__typename' => 'Dog',
                        'name' => 'Rex',
                        'good_boy' => true,
                    ],
                ],
            ],
        ], $result);
    }

    public function testGetAttributesReturnsEmptyTypesArrayIsOmitted(): void
    {
        // A UnionType whose `types()` returns an empty array should not
        // include the `types` key in attributes (covers the `if ($types)` guard).
        $type = new class extends \Rebing\GraphQL\Support\UnionType {
            protected $attributes = ['name' => 'EmptyUnion'];

            public function types(): array
            {
                return [];
            }
        };

        $attributes = $type->getAttributes();

        self::assertArrayNotHasKey('types', $attributes);
    }

    public function testGetAttributesOmitsResolveTypeWhenNotDefined(): void
    {
        // Covers the `method_exists($this, 'resolveType')` guard in UnionType::getAttributes().
        $type = new class extends \Rebing\GraphQL\Support\UnionType {
            protected $attributes = ['name' => 'NoResolverUnion'];

            public function types(): array
            {
                return [GraphQL::type('Cat')];
            }
        };

        $attributes = $type->getAttributes();

        self::assertArrayHasKey('types', $attributes);
        self::assertArrayNotHasKey('resolveType', $attributes);
    }
}
