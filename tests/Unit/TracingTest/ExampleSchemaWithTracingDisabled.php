<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\TracingTest;

use Rebing\GraphQL\Support\Contracts\ConfigConvertible;
use Rebing\GraphQL\Tests\Support\Objects\ExamplesQuery;
use Rebing\GraphQL\Tests\Support\Objects\UpdateExampleMutation;

class ExampleSchemaWithTracingDisabled implements ConfigConvertible
{
    public function toConfig(): array
    {
        return [
            'query' => [
                'examples' => ExamplesQuery::class,
            ],
            'mutation' => [
                'updateExample' => UpdateExampleMutation::class,
            ],
            'tracing' => false,
        ];
    }
}
