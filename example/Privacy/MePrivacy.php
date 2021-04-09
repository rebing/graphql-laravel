<?php

declare(strict_types = 1);

use Auth;
use Rebing\GraphQL\Support\Privacy;

class MePrivacy extends Privacy
{
    public function validate(array $queryArgs, $queryContext = null): bool
    {
        return $queryArgs['id'] == Auth::id();
    }
}
