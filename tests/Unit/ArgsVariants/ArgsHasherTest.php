<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ArgsVariants;

use PHPUnit\Framework\TestCase;
use Rebing\GraphQL\Support\ArgsVariants\ArgsHasher;

class ArgsHasherTest extends TestCase
{
    public function testSameArgsSameHash(): void
    {
        self::assertSame(ArgsHasher::hash(['year' => 2024]), ArgsHasher::hash(['year' => 2024]));
    }

    public function testDifferentArgsDifferentHash(): void
    {
        self::assertNotSame(ArgsHasher::hash(['year' => 2024]), ArgsHasher::hash(['year' => 2025]));
    }

    public function testAssocKeyOrderIsIrrelevant(): void
    {
        self::assertSame(
            ArgsHasher::hash(['a' => 1, 'b' => 2]),
            ArgsHasher::hash(['b' => 2, 'a' => 1]),
        );
    }

    public function testNestedAssocKeyOrderIsIrrelevant(): void
    {
        self::assertSame(
            ArgsHasher::hash(['filter' => ['from' => 1, 'to' => 2]]),
            ArgsHasher::hash(['filter' => ['to' => 2, 'from' => 1]]),
        );
    }

    public function testListOrderIsSignificant(): void
    {
        self::assertNotSame(
            ArgsHasher::hash(['ids' => [1, 2]]),
            ArgsHasher::hash(['ids' => [2, 1]]),
        );
    }

    public function testEmptyArgs(): void
    {
        self::assertSame(ArgsHasher::hash([]), ArgsHasher::hash([]));
        self::assertNotSame(ArgsHasher::hash([]), ArgsHasher::hash(['a' => null]));
    }

    public function testNullValueDiffersFromMissingKey(): void
    {
        self::assertNotSame(
            ArgsHasher::hash(['b' => 1]),
            ArgsHasher::hash(['a' => null, 'b' => 1]),
        );
    }

    public function testIntegerAndNumericStringDiffer(): void
    {
        self::assertNotSame(ArgsHasher::hash(['id' => 1]), ArgsHasher::hash(['id' => '1']));
    }

    public function testFalsyValuesAreAllDistinct(): void
    {
        $hashes = [
            ArgsHasher::hash(['v' => 0]),
            ArgsHasher::hash(['v' => false]),
            ArgsHasher::hash(['v' => null]),
            ArgsHasher::hash(['v' => '']),
        ];

        self::assertSame($hashes, array_values(array_unique($hashes)));
    }

    public function testNestedListOfInputObjectsKeyOrderIrrelevantListOrderSignificant(): void
    {
        self::assertSame(
            ArgsHasher::hash(['filters' => [['op' => 'eq', 'field' => 'a'], ['op' => 'gt', 'field' => 'b']]]),
            ArgsHasher::hash(['filters' => [['field' => 'a', 'op' => 'eq'], ['field' => 'b', 'op' => 'gt']]]),
        );
        self::assertNotSame(
            ArgsHasher::hash(['filters' => [['op' => 'eq'], ['op' => 'gt']]]),
            ArgsHasher::hash(['filters' => [['op' => 'gt'], ['op' => 'eq']]]),
        );
    }

    public function testEnumsNormalizeToTheirScalar(): void
    {
        self::assertSame(
            ArgsHasher::hash(['suit' => ArgsHasherTestSuit::Hearts]),
            ArgsHasher::hash(['suit' => ArgsHasherTestSuit::Hearts]),
        );
        // Documented behavior: a backed enum hashes equal to its backing value.
        self::assertSame(
            ArgsHasher::hash(['suit' => ArgsHasherTestSuit::Hearts]),
            ArgsHasher::hash(['suit' => 'H']),
        );
        self::assertNotSame(
            ArgsHasher::hash(['suit' => ArgsHasherTestSuit::Hearts]),
            ArgsHasher::hash(['suit' => ArgsHasherTestSuit::Spades]),
        );
    }

    public function testPureEnumsNormalizeToTheirName(): void
    {
        self::assertSame(
            ArgsHasher::hash(['c' => ArgsHasherTestColor::Red]),
            ArgsHasher::hash(['c' => ArgsHasherTestColor::Red]),
        );
        self::assertNotSame(
            ArgsHasher::hash(['c' => ArgsHasherTestColor::Red]),
            ArgsHasher::hash(['c' => ArgsHasherTestColor::Blue]),
        );
        // Documented behavior: a pure enum hashes equal to its case name string.
        self::assertSame(
            ArgsHasher::hash(['c' => ArgsHasherTestColor::Red]),
            ArgsHasher::hash(['c' => 'Red']),
        );
    }
}

enum ArgsHasherTestSuit: string
{
    case Hearts = 'H';
    case Spades = 'S';
}

enum ArgsHasherTestColor
{
    case Red;
    case Blue;
}
