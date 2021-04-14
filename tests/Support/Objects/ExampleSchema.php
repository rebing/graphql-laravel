<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support\Objects;

use Rebing\GraphQL\Support\Contracts\ConfigConvertible;

class ExampleSchema implements ConfigConvertible
{
    public function toConfig(): array
    {
        return [
            'query' => [
                'examplesCustom' => ExamplesQuery::class,
            ],
            'mutation' => [
                'updateExampleCustom' => UpdateExampleMutation::class,
            ],
            'middleware' => [
                ExampleMiddleware::class,
            ],
        ];
    }
}
