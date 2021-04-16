<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ValidationException;

use Rebing\GraphQL\Tests\TestCase;

class ValidationExceptionTest extends TestCase
{
    public function testLaravelValidationException(): void
    {
        $query = <<<'GRAQPHQL'
query {
    throwsValidationException
}
GRAQPHQL;

        $result = $this->graphql($query, [
            'expectErrors' => true,
        ]);

        $expected = [
            'errors' => [
                [
                    'message' => 'validation',
                    'extensions' => [
                        'category' => 'validation',
                        'validation' => [
                            'field' => [
                                    'The field field is required.',
                                ],
                        ],
                    ],
                    'locations' => [
                        [
                            'line' => 2,
                            'column' => 5,
                        ],
                    ],
                    'path' => [
                        'throwsValidationException',
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $result);
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('graphql.schemas.default', [
            'query' => [
                ThrowsValidationExceptionQuery::class,
            ],
        ]);
    }
}
