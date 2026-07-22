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
}
