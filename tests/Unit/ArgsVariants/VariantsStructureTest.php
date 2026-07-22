<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ArgsVariants;

use Rebing\GraphQL\Tests\TestCase;
use Rebing\GraphQL\Tests\Unit\ArgsVariants\Fixture\AuthorType;
use Rebing\GraphQL\Tests\Unit\ArgsVariants\Fixture\CaptureTreeQuery;
use Rebing\GraphQL\Tests\Unit\ArgsVariants\Fixture\CommentType;
use Rebing\GraphQL\Tests\Unit\ArgsVariants\Fixture\PostType;

class VariantsStructureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        CaptureTreeQuery::$tree = null;
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

    public function testConflictBetweenFragmentAndDirectSelection(): void
    {
        $this->httpGraphql('{ captureTree {
            a: comments(top: 3) { id }
            ...CommentsFrag
        } }
        fragment CommentsFrag on VariantPost {
            b: comments(top: 5) { id }
        }');

        $variants = CaptureTreeQuery::$tree['comments']['argsVariants'] ?? null;
        self::assertIsArray($variants);
        self::assertSame([['top' => 3], ['top' => 5]], array_column($variants, 'args'));
    }

    public function testConflictInsideInlineFragment(): void
    {
        $this->httpGraphql('{ captureTree {
            a: comments(top: 3) { id }
            ... on VariantPost {
                b: comments(top: 5) { id }
            }
        } }');

        $variants = CaptureTreeQuery::$tree['comments']['argsVariants'] ?? null;
        self::assertIsArray($variants);
        self::assertSame([['top' => 3], ['top' => 5]], array_column($variants, 'args'));
    }

    public function testNestedConflictUnderNonConflictingAncestor(): void
    {
        // 'author' occurs twice with identical (empty) args — no variants on
        // author itself; the divergence escalates to author.fields.comments.
        $this->httpGraphql('{ captureTree {
            x: author { comments(top: 1) { id } }
            y: author { comments(top: 2) { id } }
        } }');

        $author = CaptureTreeQuery::$tree['author'] ?? null;
        self::assertIsArray($author);
        self::assertArrayNotHasKey('argsVariants', $author);

        $variants = $author['fields']['comments']['argsVariants'] ?? null;
        self::assertIsArray($variants);
        self::assertSame([['top' => 1], ['top' => 2]], array_column($variants, 'args'));
    }

    public function testNestedConflictInsideVariantSubtree(): void
    {
        // comments diverges (variants), and inside the SAME variant (top: 1,
        // reached twice) nothing diverges — but across a1/a2's children of one
        // hash group, nested divergence must escalate into the variant subtree.
        $this->httpGraphql('{ captureTree {
            p1: author { a: comments(top: 1) { id } }
            p2: author { b: comments(top: 1) { body } }
            p3: author { c: comments(top: 9) { id } }
        } }');

        $commentsEntry = CaptureTreeQuery::$tree['author']['fields']['comments'] ?? null;
        self::assertIsArray($commentsEntry);
        $variants = $commentsEntry['argsVariants'];
        self::assertCount(2, $variants);

        // The (top: 1) variant merges subtrees of a & b (union of fields):
        $top1 = array_values(array_filter($variants, static fn (array $v): bool => ['top' => 1] === $v['args']))[0];
        self::assertArrayHasKey('id', $top1['fields']);
        self::assertArrayHasKey('body', $top1['fields']);
    }

    public function testTypenameOnlySelectionEmitsNoVariantsAndDoesNotThrow(): void
    {
        // Guards the HasFieldsType/__typename handling in collectOccurrences.
        $this->httpGraphql('{ captureTree { id __typename } }');

        self::assertIsArray(CaptureTreeQuery::$tree);
        self::assertFalse($this->treeContainsKey(CaptureTreeQuery::$tree, 'argsVariants'));
    }

    /**
     * @param array<int|string,mixed> $tree
     */
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
