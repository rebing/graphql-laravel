<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\ValidateFieldTests;

use Mockery;
use Mockery\MockInterface;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Tests\Support\Models\Comment;
use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;
use Rebing\GraphQL\Tests\TestCaseDatabase;

class ValidateFieldTest extends TestCaseDatabase
{
    use SqlAssertionTrait;

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'query' => [
                ValidateFieldsQuery::class,
            ],
        ]);

        $app['config']->set('graphql.types', [
            CommentType::class,
            PostType::class,
        ]);
    }

    public function testSelectableFalse(): void
    {
        /** @var Post $post */
        $post = factory(Post::class)
            ->create([
                'body' => 'post body',
                'title' => 'post title',
            ]);
        /** @var Comment $comment */
        $comment = factory(Comment::class)
            ->create([
                'body' => 'comment body',
                'post_id' => $post->id,
                'title' => 'comment title',
            ]);

        $query = <<<'GRAQPHQL'
{
  validateFields {
    body_selectable_false
    title
    comments {
      id
    }
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($query);

        $this->assertSqlQueries(
            <<<'SQL'
select "posts"."title", "posts"."id" from "posts";
select "comments"."id", "comments"."post_id" from "comments" where "comments"."post_id" in (?) order by "comments"."id" asc;
SQL
        );

        $expectedResult = [
            'data' => [
                'validateFields' => [
                    [
                        'body_selectable_false' => null,
                        'title' => 'post title',
                        'comments' => [
                            [
                                'id' => (string) $comment->id,
                            ],
                        ],
                    ],
                ],
            ],
        ];
        self::assertEquals($expectedResult, $result);
    }

    public function testSelectableNull(): void
    {
        /** @var Post $post */
        $post = factory(Post::class)
            ->create([
                'body' => 'post body',
                'title' => 'post title',
            ]);
        /** @var Comment $comment */
        $comment = factory(Comment::class)
            ->create([
                'body' => 'comment body',
                'post_id' => $post->id,
                'title' => 'comment title',
            ]);

        $query = <<<'GRAQPHQL'
{
  validateFields {
    body_selectable_null
    title
    comments {
      id
    }
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($query);

        $this->assertSqlQueries(
            <<<'SQL'
select "posts"."body", "posts"."title", "posts"."id" from "posts";
select "comments"."id", "comments"."post_id" from "comments" where "comments"."post_id" in (?) order by "comments"."id" asc;
SQL
        );

        $expectedResult = [
            'data' => [
                'validateFields' => [
                    [
                        'body_selectable_null' => 'post body',
                        'title' => 'post title',
                        'comments' => [
                            [
                                'id' => (string) $comment->id,
                            ],
                        ],
                    ],
                ],
            ],
        ];
        self::assertEquals($expectedResult, $result);
    }

    public function testSelectableTrue(): void
    {
        /** @var Post $post */
        $post = factory(Post::class)
            ->create([
                'body' => 'post body',
                'title' => 'post title',
            ]);
        /** @var Comment $comment */
        $comment = factory(Comment::class)
            ->create([
                'body' => 'comment body',
                'post_id' => $post->id,
                'title' => 'comment title',
            ]);

        $query = <<<'GRAQPHQL'
{
  validateFields {
    body_selectable_true
    title
    comments {
      id
    }
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($query);

        $this->assertSqlQueries(
            <<<'SQL'
select "posts"."body", "posts"."title", "posts"."id" from "posts";
select "comments"."id", "comments"."post_id" from "comments" where "comments"."post_id" in (?) order by "comments"."id" asc;
SQL
        );

        $expectedResult = [
            'data' => [
                'validateFields' => [
                    [
                        'body_selectable_true' => 'post body',
                        'title' => 'post title',
                        'comments' => [
                            [
                                'id' => (string) $comment->id,
                            ],
                        ],
                    ],
                ],
            ],
        ];
        self::assertEquals($expectedResult, $result);
    }

    public function testPrivacyClosureAllowed(): void
    {
        factory(Post::class)
            ->create([
                'title' => 'post title',
            ]);

        $query = <<<'GRAQPHQL'
{
  validateFields {
    title_privacy_closure_allowed
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($query);

        $this->assertSqlQueries(
            <<<'SQL'
select "posts"."title", "posts"."id" from "posts";
SQL
        );

        $expectedResult = [
            'data' => [
                'validateFields' => [
                    [
                        'title_privacy_closure_allowed' => 'post title',
                    ],
                ],
            ],
        ];
        self::assertEquals($expectedResult, $result);
    }

    public function testPrivacyClosureDenied(): void
    {
        factory(Post::class)
            ->create([
                'title' => 'post title',
            ]);

        $query = <<<'GRAQPHQL'
{
  validateFields {
    title_privacy_closure_denied
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($query);

        $this->assertSqlQueries(
            <<<'SQL'
select "posts"."id" from "posts";
SQL
        );

        $expectedResult = [
            'data' => [
                'validateFields' => [
                    [
                        'title_privacy_closure_denied' => null,
                    ],
                ],
            ],
        ];
        self::assertEquals($expectedResult, $result);
    }

    public function testPrivacyClassAllowed(): void
    {
        factory(Post::class)
            ->create([
                'title' => 'post title',
            ]);

        $query = <<<'GRAQPHQL'
{
  validateFields {
    title_privacy_class_allowed
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($query);

        $this->assertSqlQueries(
            <<<'SQL'
select "posts"."title", "posts"."id" from "posts";
SQL
        );

        $expectedResult = [
            'data' => [
                'validateFields' => [
                    [
                        'title_privacy_class_allowed' => 'post title',
                    ],
                ],
            ],
        ];
        self::assertEquals($expectedResult, $result);
    }

    public function testPrivacyClassDenied(): void
    {
        factory(Post::class)
            ->create([
                'title' => 'post title',
            ]);

        $query = <<<'GRAQPHQL'
{
  validateFields {
    title_privacy_class_denied
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($query);

        $this->assertSqlQueries(
            <<<'SQL'
select "posts"."id" from "posts";
SQL
        );

        $expectedResult = [
            'data' => [
                'validateFields' => [
                    [
                        'title_privacy_class_denied' => null,
                    ],
                ],
            ],
        ];
        self::assertEquals($expectedResult, $result);
    }

    public function testPrivacyClassMultipleTimesIsCalledMultipleTimes(): void
    {
        factory(Post::class)
            ->create([
                'title' => 'post title',
            ]);

        /** @var PrivacyAllowed|MockInterface $privacyMock */
        $privacyMock = $this->instance(
            PrivacyAllowed::class,
            Mockery::mock(PrivacyAllowed::class)->makePartial()
        );
        $privacyMock
            ->expects('validate')
            ->andReturn('true')
            ->times(2);

        $query = <<<'GRAQPHQL'
{
  validateFields {
    title_privacy_class_allowed
    title_privacy_class_allowed_called_twice
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($query);

        $this->assertSqlQueries(
            <<<'SQL'
select "posts"."title", "posts"."id" from "posts";
SQL
        );
        $expectedResult = [
            'data' => [
                'validateFields' => [
                    [
                        'title_privacy_class_allowed' => 'post title',
                        'title_privacy_class_allowed_called_twice' => 'post title',
                    ],
                ],
            ],
        ];
        self::assertEquals($expectedResult, $result);
    }

    /**
     * Note: actual assertion happens in \Rebing\GraphQL\Tests\Database\SelectFields\ValidateFieldTests\PostType::fields
     * within the closure for the field `title_privacy_closure_args`.
     */
    public function testPrivacyClosureReceivesQueryArgs(): void
    {
        factory(Post::class)
            ->create([
                'title' => 'post title',
            ]);

        $query = <<<'GRAQPHQL'
{
  validateFields(arg_from_query: true) {
    title_privacy_closure_args(arg_from_field: true)
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($query);

        $this->assertSqlQueries(
            <<<'SQL'
select "posts"."title", "posts"."id" from "posts";
SQL
        );

        $expectedResult = [
            'data' => [
                'validateFields' => [
                    [
                        'title_privacy_closure_args' => 'post title',
                    ],
                ],
            ],
        ];
        self::assertEquals($expectedResult, $result);
    }

    /**
     * Note: actual assertion happens in \Rebing\GraphQL\Tests\Database\SelectFields\ValidateFieldTests\PrivacyArgs::validate.
     */
    public function testPrivacyClassReceivesQueryArgs(): void
    {
        factory(Post::class)
            ->create([
                'title' => 'post title',
            ]);

        $query = <<<'GRAQPHQL'
{
  validateFields(arg_from_query: true) {
    title_privacy_class_args(arg_from_field: true)
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($query);

        $this->assertSqlQueries(
            <<<'SQL'
select "posts"."title", "posts"."id" from "posts";
SQL
        );

        $expectedResult = [
            'data' => [
                'validateFields' => [
                    [
                        'title_privacy_class_args' => 'post title',
                    ],
                ],
            ],
        ];
        self::assertEquals($expectedResult, $result);
    }

    public function testPrivacyWrongType(): void
    {
        factory(Post::class)
            ->create([
                'title' => 'post title',
            ]);

        $query = <<<'GRAQPHQL'
{
  validateFields {
    title_privacy_wrong_type
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($query, [
            'expectErrors' => true,
        ]);

        $this->assertSqlQueries('');

        $expectedResult = [
            'errors' => [
                [
                    'message' => 'Internal server error',
                    'extensions' => [
                        'debugMessage' => 'Unsupported use of \'privacy\' configuration on field \'title_privacy_wrong_type\'.',
                    ],
                    'locations' => [
                        [
                            'line' => 2,
                            'column' => 3,
                        ],
                    ],
                    'path' => [
                        'validateFields',
                    ],
                ],
            ],
            'data' => [
                'validateFields' => null,
            ],
        ];
        self::assertEquals($expectedResult, $result);
    }

    /**
     * Note: actual assertion happens in \Rebing\GraphQL\Tests\Database\SelectFields\ValidateFieldTests\PostType::fields
     * within the closure for the field `title_privacy_closure_context`.
     */
    public function testPrivacyClosureReceivesContext(): void
    {
        factory(Post::class)
            ->create([
                'title' => 'post title',
            ]);

        $query = <<<'GRAQPHQL'
{
  validateFields {
    title_privacy_closure_query_context
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $opts = [
            'context' => [
                'arg_from_context_true' => true,
                'arg_from_context_false' => false,
            ],
        ];

        $result = GraphQL::query($query, null, $opts);

        $this->assertSqlQueries(
            <<<'SQL'
select "posts"."title", "posts"."id" from "posts";
SQL
        );

        $expectedResult = [
            'data' => [
                'validateFields' => [
                    [
                        'title_privacy_closure_query_context' => 'post title',
                    ],
                ],
            ],
        ];
        self::assertEquals($expectedResult, $result);
    }

    /**
     * Note: actual assertion happens in \Rebing\GraphQL\Tests\Database\SelectFields\ValidateFieldTests\PrivacyQueryContext::validate.
     */
    public function testPrivacyClassReceivesQueryContext(): void
    {
        factory(Post::class)
            ->create([
                'title' => 'post title',
            ]);

        $query = <<<'GRAQPHQL'
{
  validateFields {
    title_privacy_class_query_context
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $opts = [
            'context' => [
                'arg_from_context_true' => true,
                'arg_from_context_false' => false,
            ],
        ];

        $result = GraphQL::query($query, null, $opts);

        $this->assertSqlQueries(
            <<<'SQL'
select "posts"."title", "posts"."id" from "posts";
SQL
        );

        $expectedResult = [
            'data' => [
                'validateFields' => [
                    [
                        'title_privacy_class_query_context' => 'post title',
                    ],
                ],
            ],
        ];
        self::assertEquals($expectedResult, $result);
    }
}
