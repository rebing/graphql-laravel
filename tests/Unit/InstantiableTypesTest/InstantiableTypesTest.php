<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\InstantiableTypesTest;

use Carbon\Carbon;
use Rebing\GraphQL\Tests\TestCase;

class InstantiableTypesTest extends TestCase
{
    public function testSomething(): void
    {
        $query = <<<'GRAQPHQL'
{
    user {
        default: dateOfBirth,
        formattedDifferent: dateOfBirth(format: "Y-m-d"),
        relative: dateOfBirth(relative: true),
        alias: createdAt
    }
}
GRAQPHQL;

        $result = $this->graphql($query);

        $dateOfBirth = Carbon::now()->addMonth()->startOfDay();
        $createdAt = Carbon::now()->startOfDay();

        $expectedResult = [
            'data' => [
                'user' => [
                    'default'            => $dateOfBirth->format('Y-m-d H:i'),
                    'formattedDifferent' => $dateOfBirth->format('Y-m-d'),
                    'relative'           => '4 weeks from now',
                    'alias'              => $createdAt->format('Y-m-d H:i'),
                ],
            ],
        ];
        $this->assertSame($expectedResult, $result);
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'query' => [
                'user' => UserQuery::class,
            ],
        ]);
        $app['config']->set('graphql.types', [
            'UserType' => UserType::class,
        ]);
    }
}
