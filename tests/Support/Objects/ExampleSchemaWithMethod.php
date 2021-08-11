<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support\Objects;

class ExampleSchemaWithMethod extends ExampleSchema
{
    public function toConfig(): array
    {
        return array_merge(parent::toConfig(), ['method' => ['post']]);
    }
}
