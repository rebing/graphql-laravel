<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Support\Contracts;

interface ConfigConvertible
{
    /**
     * @return array<array>
     */
    public function toConfig(): array;
}
