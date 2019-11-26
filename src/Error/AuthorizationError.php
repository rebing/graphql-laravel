<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Error;

use GraphQL\Error\ClientAware;
use GraphQL\Error\Error;

class AuthorizationError extends Error implements ClientAware
{
    public function isClientSafe(): bool
    {
        return true;
    }

    public function getCategory(): string
    {
        return 'authorization';
    }
}
