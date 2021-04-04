<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\ValidationOfFieldArguments;

use Composer\InstalledVersions;
use Illuminate\Support\MessageBag;
use Rebing\GraphQL\Tests\TestCase;

class ValidationOfFieldArgumentsTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
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

        $result = $this->graphql($graphql, [
            'expectErrors' => true,
            'variables' => [
                'profileId' => 100,
                'height' => 'not_correct',
            ],
        ]);

        /** @var MessageBag $messageBag */
        $messageBag = $result['errors'][0]['extensions']['validation'];
        $expectedMessages = [
            'The profile.fields.name.args.include middle names format is invalid.',
            'The profile.fields.height.args.unit format is invalid.',
        ];

        // See https://github.com/orchestral/testbench-core/commit/6c9c77b2e978890cb6a2712251ddab5eb1b79049
        if ($this->orchestraTestbenchCoreVersionBelow('6.17.1.0')) {
            $expectedMessages[] = 'The profile.args.profile id may not be greater than 10.';
        } else {
            $expectedMessages[] = 'The profile.args.profile id must not be greater than 10.';
        }

        $this->assertSame($expectedMessages, $messageBag->all());
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

        $result = $this->graphql($graphql, [
            'expectErrors' => true,
            'variables' => [],
        ]);

        /** @var MessageBag $messageBag */
        $messageBag = $result['errors'][0]['extensions']['validation'];
        $expectedMessages = [
            'The alias.args.type format is invalid.',
        ];
        $this->assertSame($expectedMessages, $messageBag->all());
    }

    private function orchestraTestbenchCoreVersionBelow(string $versionString): bool
    {
        return InstalledVersions::getVersion('orchestra/testbench-core') < $versionString;
    }
}
