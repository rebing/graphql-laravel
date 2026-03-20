<?php

declare(strict_types = 1);

use Auth;
use Rebing\GraphQL\Support\Privacy;

class MePrivacy extends Privacy
{
    public function validate(array $fieldArgs, $queryContext = null): bool
    {
        return $fieldArgs['id'] == Auth::id();
    }
}
