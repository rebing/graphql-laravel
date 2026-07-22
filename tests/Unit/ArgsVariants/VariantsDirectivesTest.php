<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ArgsVariants;

use Rebing\GraphQL\Tests\TestCase;
use Rebing\GraphQL\Tests\Unit\ArgsVariants\Fixture\AuthorType;
use Rebing\GraphQL\Tests\Unit\ArgsVariants\Fixture\CaptureTreeQuery;
use Rebing\GraphQL\Tests\Unit\ArgsVariants\Fixture\CommentType;
use Rebing\GraphQL\Tests\Unit\ArgsVariants\Fixture\PostType;

class VariantsDirectivesTest extends TestCase
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

    public function testExcludedRawDivergentOccurrenceForcesVariantForActiveOne(): void
    {
        // The spec §1.1 example: without variants, the legacy merged args
        // would be poisoned by the excluded occurrence (top: 5, last wins).
        $this->httpGraphql('{ captureTree {
            a: comments(top: 3) @include(if: true) { id }
            b: comments(top: 5) @include(if: false) { id }
        } }');

        $variants = CaptureTreeQuery::$tree['comments']['argsVariants'] ?? null;
        self::assertIsArray($variants);
        self::assertCount(1, $variants);
        self::assertSame(['top' => 3], array_values($variants)[0]['args']);
    }

    public function testSkipBehavesLikeNegatedInclude(): void
    {
        $this->httpGraphql('{ captureTree {
            a: comments(top: 3) { id }
            b: comments(top: 5) @skip(if: true) { id }
        } }');

        $variants = CaptureTreeQuery::$tree['comments']['argsVariants'] ?? null;
        self::assertIsArray($variants);
        self::assertCount(1, $variants);
        self::assertSame(['top' => 3], array_values($variants)[0]['args']);
    }

    public function testVariableDrivenDirectiveBothWays(): void
    {
        $query = 'query Q($flag: Boolean!) { captureTree {
            a: comments(top: 3) { id }
            b: comments(top: 5) @include(if: $flag) { id }
        } }';

        $this->httpGraphql($query, ['variables' => ['flag' => true]]);
        self::assertCount(2, CaptureTreeQuery::$tree['comments']['argsVariants']);

        CaptureTreeQuery::$tree = null;
        $this->httpGraphql($query, ['variables' => ['flag' => false]]);
        $variants = CaptureTreeQuery::$tree['comments']['argsVariants'];
        self::assertCount(1, $variants);
        self::assertSame(['top' => 3], array_values($variants)[0]['args']);
    }

    public function testAllOccurrencesExcludedEmitsNoVariants(): void
    {
        $this->httpGraphql('{ captureTree {
            id
            a: comments(top: 3) @skip(if: true) { id }
            b: comments(top: 5) @skip(if: true) { id }
        } }');

        self::assertArrayNotHasKey('argsVariants', CaptureTreeQuery::$tree['comments'] ?? []);
    }

    public function testDirectiveOnFragmentSpreadExcludesItsFields(): void
    {
        $this->httpGraphql('{ captureTree {
            a: comments(top: 3) { id }
            ...CommentsFrag @include(if: false)
        } }
        fragment CommentsFrag on VariantPost {
            b: comments(top: 5) { id }
        }');

        $variants = CaptureTreeQuery::$tree['comments']['argsVariants'] ?? null;
        self::assertIsArray($variants);
        self::assertCount(1, $variants);
        self::assertSame(['top' => 3], array_values($variants)[0]['args']);
    }
}
