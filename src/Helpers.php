<?php

declare(strict_types=1);

namespace Rebing\GraphQL;

class Helpers
{
    public static function isLumen(): bool
    {
        return class_exists('Laravel\Lumen\Application');
    }
}
