<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ExecutionMiddlewareTest;

use Rebing\GraphQL\Tests\TestCase;

class ExecutionMiddlewareTest extends TestCase
{
    public function testMiddlewareCanReturnResponse(): void
    {
        $this->app['config']->set('graphql.execution_middleware', [
            CacheMiddleware::class,
        ]);

        $result = $this->httpGraphql($this->queries['examplesWithVariables'], [
            'variables' => [
                'index' => 1,
            ],
        ]);

        $expected = [
            'data' => [
                'examples' => [
                    [
                        'test' => 'Cached response',
                    ],
                ],
            ],
        ];
        self::assertSame($expected, $result);
    }

    public function testMiddlewareCanMutateArgs(): void
    {
        $this->app['config']->set('graphql.execution_middleware', [
            ChangeVariableMiddleware::class,
        ]);

        $result = $this->httpGraphql($this->queries['examplesWithVariables'], [
            'variables' => [
                'index' => '1',
            ],
        ]);

        $expected = [
            'data' => [
                'examples' => [
                    [
                        'test' => 'Example 2',
                    ],
                ],
            ],
        ];
        self::assertSame($expected, $result);
    }

    public function testMiddlewareCanMutateQueryAndSendParsedQueryAlong(): void
    {
        $this->app['config']->set('graphql.execution_middleware', [
            ChangeQueryArgTypeMiddleware::class,
        ]);

        $result = $this->httpGraphql($this->queries['examplesWithWrongTypeOfArgument'], [
            'variables' => [
                'indexVariable' => 1,
            ],
        ]);

        $expected = [
            'data' => [
                'examples' => [
                    [
                        'test' => 'Example 2',
                    ],
                ],
            ],
        ];
        self::assertSame($expected, $result);
    }

    public function testOnlyGlobalMiddleware(): void
    {
        $this->app['config']->set('graphql.execution_middleware', [
            MiddlewareGlobal::class,
        ]);
        $this->app['config']->set('graphql.schemas.default.execution_middleware', null);

        $result = $this->httpGraphql($this->queries['examples']);

        $expected = [
            'data' => [
                'examples' => [
                    [
                        'test' => MiddlewareGlobal::class,
                    ],
                ],
            ],
        ];
        self::assertSame($expected, $result);
    }

    public function testOnlyPerSchemaMiddleware(): void
    {
        $this->app['config']->set('graphql.execution_middleware', null);
        $this->app['config']->set('graphql.schemas.default.execution_middleware', [
            MiddlewarePerSchema::class,
        ]);

        $result = $this->httpGraphql($this->queries['examples']);

        $expected = [
            'data' => [
                'examples' => [
                    [
                        'test' => MiddlewarePerSchema::class,
                    ],
                ],
            ],
        ];
        self::assertSame($expected, $result);
    }

    public function testPerSchemaMiddlewareOverridesGlobalMiddleware(): void
    {
        $this->app['config']->set('graphql.execution_middleware', [
            MiddlewareGlobal::class,
        ]);
        $this->app['config']->set('graphql.schemas.default.execution_middleware', [
            MiddlewarePerSchema::class,
        ]);

        $result = $this->httpGraphql($this->queries['examples']);

        $expected = [
            'data' => [
                'examples' => [
                    [
                        'test' => MiddlewarePerSchema::class,
                    ],
                ],
            ],
        ];
        self::assertSame($expected, $result);
    }
}
