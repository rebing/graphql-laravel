<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\AliasArguments;

use PHPUnit\Framework\Attributes\Group;
use Rebing\GraphQL\Tests\TestCase;
use Rebing\GraphQL\Tests\Unit\AliasArguments\Stubs\AuthorInputObject;
use Rebing\GraphQL\Tests\Unit\AliasArguments\Stubs\BookInputObject;
use Rebing\GraphQL\Tests\Unit\AliasArguments\Stubs\CreateBookMutation;
use Rebing\GraphQL\Tests\Unit\AliasArguments\Stubs\ExampleType;

/**
 * Tests for AliasArguments with circular type references.
 *
 * These tests verify the fix for exponential time complexity when input types
 * have circular references (e.g., Book -> Author -> Book).
 *
 * On master (without the fix), these tests would timeout or take 10+ seconds
 * because the old code traversed ALL possible fields in the type schema,
 * causing exponential explosion with circular references.
 *
 * With the fix, the code only traverses fields present in actual request data,
 * completing in milliseconds.
 */
#[Group('alias-arguments')]
class AliasArgumentsCircularTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'mutation' => [
                CreateBookMutation::class,
            ],
        ]);

        $app['config']->set('graphql.types', [
            ExampleType::class,
            BookInputObject::class,
            AuthorInputObject::class,
        ]);
    }

    /**
     * Test that circular type references with aliases complete quickly.
     *
     * This test will FAIL on master (without the fix) by exhausting memory:
     * - The old getAliasesInFields() traverses ALL fields in the type schema
     * - With circular references (Book <-> Author with multiple paths), this causes exponential traversal
     * - Each type has 8 fields, 3 of which reference the other type
     * - At depth N, the old algorithm explores 3^N paths through the type graph
     * - With maxDepth=13 (from our deeply nested request), memory exhausts at 256MB+
     *
     * With the fix, it completes in ~35MB / <1 second because:
     * - We only traverse fields that exist in the actual request data
     * - Simple request = simple traversal, regardless of type schema complexity
     *
     * To verify this test fails on master, run:
     *   git stash
     *   git checkout upstream/master -- src/Support/AliasArguments/AliasArguments.php
     *   vendor/bin/phpunit tests/Unit/AliasArguments/AliasArgumentsCircularTest.php
     *   git checkout HEAD -- src/Support/AliasArguments/AliasArguments.php
     *   git stash pop
     */
    public function testCircularTypeReferencesWithAliasesCompleteQuickly(): void
    {
        $query = '
            mutation ($book: BookInputObject) {
                createBook(book: $book) {
                    test
                }
            }
        ';

        $startTime = microtime(true);

        // Create deeply nested data to increase maxDepth
        // The deeper the request data, the more the old algorithm has to traverse
        // ALL possible type paths up to that depth
        $response = $this->httpGraphql($query, [
            'variables' => [
                'book' => [
                    'title' => 'Book Level 1',
                    'isbn' => '111',
                    'authors' => [
                        [
                            'name' => 'Author Level 2',
                            'books' => [
                                [
                                    'title' => 'Book Level 3',
                                    'authors' => [
                                        [
                                            'name' => 'Author Level 4',
                                            'books' => [
                                                [
                                                    'title' => 'Book Level 5',
                                                    'authors' => [
                                                        [
                                                            'name' => 'Author Level 6',
                                                            'books' => [
                                                                [
                                                                    'title' => 'Book Level 7',
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $elapsedTime = microtime(true) - $startTime;

        // Assert the response is correct (check aliases are applied)
        $arguments = \Safe\json_decode($response['data']['createBook']['test'], true);

        self::assertArrayHasKey('book', $arguments);
        self::assertEquals('Book Level 1', $arguments['book']['title_alias']);

        // Assert it completed quickly (< 2 seconds)
        // On master without the fix, this takes significantly longer due to exponential traversal
        self::assertLessThan(
            2.0,
            $elapsedTime,
            \sprintf(
                'Mutation with circular type references took %.2f seconds. ' .
                'This suggests the exponential time complexity bug is present. ' .
                'Expected < 2 seconds.',
                $elapsedTime,
            ),
        );
    }

    /**
     * Test nested circular references still work correctly with aliases.
     *
     * This is a correctness test (not a performance test) that verifies
     * when we send nested circular data, aliases are correctly applied
     * at each level of nesting.
     *
     * Note: This test passes on both master and the fix because the nesting
     * is shallow (3 levels). The performance issue only manifests with
     * deeper nesting as shown in testCircularTypeReferencesWithAliasesCompleteQuickly.
     */
    public function testNestedCircularReferencesApplyAliasesCorrectly(): void
    {
        $query = '
            mutation ($book: BookInputObject) {
                createBook(book: $book) {
                    test
                }
            }
        ';

        $response = $this->httpGraphql($query, [
            'variables' => [
                'book' => [
                    'title' => 'Original Book',
                    'isbn' => '111-1-11-111111-1',
                    'authors' => [
                        [
                            'name' => 'Author One',
                            'country' => 'UK',
                            'books' => [
                                [
                                    'title' => 'Another Book by Author One',
                                    'isbn' => '222-2-22-222222-2',
                                    // Stop recursion here - no more authors
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $arguments = \Safe\json_decode($response['data']['createBook']['test'], true);

        // Verify top-level book aliases
        self::assertEquals('Original Book', $arguments['book']['title_alias']);
        self::assertEquals('111-1-11-111111-1', $arguments['book']['isbn_alias']);

        // Verify author aliases
        self::assertEquals('Author One', $arguments['book']['authors'][0]['name_alias']);
        self::assertEquals('UK', $arguments['book']['authors'][0]['country_alias']);

        // Verify nested book aliases (the circular reference)
        $nestedBook = $arguments['book']['authors'][0]['books'][0];
        self::assertEquals('Another Book by Author One', $nestedBook['title_alias']);
        self::assertEquals('222-2-22-222222-2', $nestedBook['isbn_alias']);
    }
}
