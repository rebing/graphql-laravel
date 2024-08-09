<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ExecutionMiddlewareTest;

use Illuminate\Auth\GenericUser;
use Rebing\GraphQL\Support\ExecutionMiddleware\AddAuthUserContextValueMiddleware;
use Rebing\GraphQL\Support\ExecutionMiddleware\ValidateOperationParamsMiddleware;
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

    public function testAddAuthUserContextValueMiddleware(): void
    {
        $this->app['config']->set('graphql.execution_middleware', [
            AddAuthUserContextValueMiddleware::class,
        ]);
        $this->app['config']->set('graphql.schemas.default.query', [
            ReturnAuthenticatableUserQuery::class,
        ]);

        $graphql = <<<'GRAPHQL'
{
    returnAuthenticatableUser
}
GRAPHQL;

        $this->actingAs(new GenericUser([]));
        $result = $this->httpGraphql($graphql);

        $expected = [
            'data' => [
                'returnAuthenticatableUser' => 'id',
            ],
        ];
        self::assertSame($expected, $result);
    }

    public function testValidateOperationParamsMiddlewareWithInvalidParams(): void
    {
        $this->app['config']->set('graphql.execution_middleware', [
            ValidateOperationParamsMiddleware::class,
        ]);

        $result = $this->httpGraphql($this->queries['examples'], [
            'expectErrors' => true,
            'variables' => [
                []
            ],
        ]);

        $expected = [
            'errors' => [
                [
                    'message' => 'GraphQL Request parameter "variables" must be object or JSON string parsed to object, but got [[]]',
                    'extensions' => [
                    ],
                ],
            ],
        ];

        self::assertEquals($expected, $result);
    }
}
