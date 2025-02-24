<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ValidationOfFieldArguments;

use Rebing\GraphQL\Tests\TestCase;

class ValidationOfFieldArgumentsTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('graphql.types', [
            'AccountType' => AccountType::class,
            'ProfileType' => ProfileType::class,
        ]);

        $app['config']->set('graphql.schemas.default', [
            'query' => [
                TestQuery::class,
            ],
        ]);
    }

    public function testRulesTakesEffect(): void
    {
        $graphql = <<<'GRAPHQL'
query ($profileId: Int, $height: String) {
  test {
    id
    __typename
    profile(profileId: $profileId) {
      name(includeMiddleNames: "maybe")
      height(unit: $height)
    }

  }
}
GRAPHQL;

        $result = $this->httpGraphql($graphql, [
            'expectErrors' => true,
            'variables' => [
                'profileId' => 100,
                'height' => 'not_correct',
            ],
        ]);

        $expected = [
            'errors' => [
                [
                    'message' => 'validation',
                    'extensions' => [
                        'category' => 'validation',
                        'validation' => [
                            'profile.fields.name.args.includeMiddleNames' => [
                                // The profile.fields.name.args.include middle names format is invalid.',
                                trans('validation.regex', ['attribute' => 'profile.fields.name.args.include middle names']),
                            ],
                            'profile.fields.height.args.unit' => [
                                // 'The profile.fields.height.args.unit format is invalid.
                                trans('validation.regex', ['attribute' => 'profile.fields.height.args.unit']),
                            ],
                            'profile.args.profileId' => [
                                // The profile.args.profile id must not be greater than 10.
                                trans('validation.max.numeric', ['attribute' => 'profile.args.profile id', 'max' => 10]),
                            ],
                        ],
                    ],
                    'locations' => [
                        [
                            'line' => 2,
                            'column' => 3,
                        ],
                    ],
                    'path' => [
                        'test',
                    ],
                ],
            ],
            'data' => [
                'test' => null,
            ],
        ];

        self::assertEquals($expected, $result);
    }

    public function testOnlyApplicableRulesTakesEffect(): void
    {
        $graphql = <<<'GRAPHQL'
query {
  test {
    id
    alias(type:"not_it")
  }
}
GRAPHQL;

        $result = $this->httpGraphql($graphql, [
            'expectErrors' => true,
            'variables' => [],
        ]);

        $expected = [
            'errors' => [
                [
                    'message' => 'validation',
                    'extensions' => [
                        'category' => 'validation',
                        'validation' => [
                            'alias.args.type' => [
                                // The alias.args.type format is invalid.
                                trans('validation.regex', ['attribute' => 'alias.args.type']),
                            ],
                        ],
                    ],
                    'locations' => [
                        [
                            'line' => 2,
                            'column' => 3,
                        ],
                    ],
                    'path' => [
                        'test',
                    ],
                ],
            ],
            'data' => [
                'test' => null,
            ],
        ];
        self::assertEquals($expected, $result);
    }
}
