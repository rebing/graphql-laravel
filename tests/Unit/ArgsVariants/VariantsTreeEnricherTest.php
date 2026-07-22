<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ArgsVariants;

use Rebing\GraphQL\Tests\TestCase;
use Rebing\GraphQL\Tests\Unit\ArgsVariants\Fixture\AuthorType;
use Rebing\GraphQL\Tests\Unit\ArgsVariants\Fixture\CaptureTreeQuery;
use Rebing\GraphQL\Tests\Unit\ArgsVariants\Fixture\CommentType;
use Rebing\GraphQL\Tests\Unit\ArgsVariants\Fixture\PostType;

class VariantsTreeEnricherTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        CaptureTreeQuery::$tree = null;
        CaptureTreeQuery::$info = null;
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'query' => [
                CaptureTreeQuery::class,
            ],
        ]);
        $app['config']->set('graphql.types', [
            AuthorType::class,
            CommentType::class,
            PostType::class,
        ]);
    }

    public function testConflictFreeQueryProducesByteIdenticalLegacyTree(): void
    {
        $this->httpGraphql('{ captureTree { id title comments(top: 3) { id } } }');

        self::assertNotNull(CaptureTreeQuery::$tree);
        self::assertNotNull(CaptureTreeQuery::$info);
        // True byte identity: serialize() of the raw tree would throw (Type
        // configs contain closures), so Type instances — singletons per built
        // schema — are replaced by their spl_object_id. This asserts key
        // order, scalar types, AND Type-instance identity byte-for-byte.
        self::assertSame(
            serialize($this->comparableTree(CaptureTreeQuery::$info->lookAhead()->queryPlan())),
            serialize($this->comparableTree(CaptureTreeQuery::$tree)),
        );
    }

    /**
     * @param array<int|string,mixed> $tree
     * @return array<int|string,mixed>
     */
    protected function comparableTree(array $tree): array
    {
        $out = [];

        foreach ($tree as $key => $value) {
            if ($value instanceof \GraphQL\Type\Definition\Type) {
                $out[$key] = 'type#' . spl_object_id($value);
            } elseif (\is_array($value)) {
                $out[$key] = $this->comparableTree($value);
            } else {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    public function testConflictFreeQueryHasNoArgsVariantsKeyAnywhere(): void
    {
        $this->httpGraphql('{ captureTree { id comments(top: 3) { id } author { comments(top: 3) { id } } } }');

        self::assertNotNull(CaptureTreeQuery::$tree);
        self::assertFalse($this->treeContainsKey(CaptureTreeQuery::$tree, 'argsVariants'));
    }

    /** @param array<int|string,mixed> $tree */
    protected function treeContainsKey(array $tree, string $needle): bool
    {
        foreach ($tree as $key => $value) {
            if ($key === $needle) {
                return true;
            }

            if (\is_array($value) && $this->treeContainsKey($value, $needle)) {
                return true;
            }
        }

        return false;
    }

    public function testAliasedDuplicatesWithDifferentArgsEmitVariants(): void
    {
        $this->httpGraphql('{ captureTree { a: comments(top: 3) { id } b: comments(top: 5) { id body } } }');

        $entry = CaptureTreeQuery::$tree['comments'] ?? null;
        self::assertIsArray($entry);
        self::assertArrayHasKey('argsVariants', $entry);

        $variants = $entry['argsVariants'];
        self::assertCount(2, $variants);
        self::assertSame(
            [\Rebing\GraphQL\Support\ArgsVariants\ArgsHasher::hash(['top' => 3]), \Rebing\GraphQL\Support\ArgsVariants\ArgsHasher::hash(['top' => 5])],
            array_keys($variants),
        );
        self::assertSame([['top' => 3], ['top' => 5]], array_values(array_column($variants, 'args')));

        // Variant subtrees carry the legacy shape, per occurrence:
        $first = array_values($variants)[0];
        self::assertArrayHasKey('id', $first['fields']);
        self::assertArrayNotHasKey('body', $first['fields']);
        $second = array_values($variants)[1];
        self::assertArrayHasKey('id', $second['fields']);
        self::assertArrayHasKey('body', $second['fields']);
        self::assertSame([], $second['fields']['id']['fields']);
        self::assertSame([], $second['fields']['id']['args']);
    }

    public function testAliasedDuplicatesWithSameArgsEmitNoVariants(): void
    {
        $this->httpGraphql('{ captureTree { a: comments(top: 3) { id } b: comments(top: 3) { body } } }');

        self::assertFalse($this->treeContainsKey(CaptureTreeQuery::$tree, 'argsVariants'));
    }

    public function testVariantsViaVariables(): void
    {
        $this->httpGraphql(
            'query Q($t1: Int, $t2: Int) { captureTree { a: comments(top: $t1) { id } b: comments(top: $t2) { id } } }',
            ['variables' => ['t1' => 1, 't2' => 2]],
        );

        $variants = CaptureTreeQuery::$tree['comments']['argsVariants'] ?? null;
        self::assertIsArray($variants);
        self::assertSame([['top' => 1], ['top' => 2]], array_values(array_column($variants, 'args')));
    }

    public function testLegacyMergedEntryIsStillPresentNextToVariants(): void
    {
        $this->httpGraphql('{ captureTree { a: comments(top: 3) { id } b: comments(top: 5) { id } } }');

        $entry = CaptureTreeQuery::$tree['comments'];
        // Legacy merge semantics (arrayMergeDeep, last wins) must be untouched:
        self::assertSame(['top' => 5], $entry['args']);
        self::assertArrayHasKey('id', $entry['fields']);
        self::assertArrayHasKey('type', $entry);
    }
}
