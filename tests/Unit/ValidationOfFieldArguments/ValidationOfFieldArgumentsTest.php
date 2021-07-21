<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ValidationOfFieldArguments;

use Composer\InstalledVersions;
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
                                'The profile.fields.name.args.include middle names format is invalid.',
                            ],
                            'profile.fields.height.args.unit' => [
                                'The profile.fields.height.args.unit format is invalid.',
                            ],
                            'profile.args.profileId' => [
                                'The profile.args.profile id must not be greater than 10.',
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

        if ($this->orchestraTestbenchCoreVersionBelow('6.17.1.0')) {
            $expected['errors'][0]['extensions']['validation']['profile.args.profileId'] = [
                'The profile.args.profile id may not be greater than 10.',
            ];
        }

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
        dd($result);
        $expected = [
            'errors' => [
                [
                    'message' => 'validation',
                    'extensions' => [
                        'category' => 'validation',
                        'validation' => [
                            'alias.args.type' => [
                                'The alias.args.type format is invalid.',
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

    private function orchestraTestbenchCoreVersionBelow(string $versionString): bool
    {
        return InstalledVersions::getVersion('orchestra/testbench-core') < $versionString;
    }
}
