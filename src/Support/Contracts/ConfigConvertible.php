<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Support\Contracts;

interface ConfigConvertible
{
    public function toConfig(): array;
}
