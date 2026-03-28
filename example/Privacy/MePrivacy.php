<?php

declare(strict_types = 1);

use Auth;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Privacy;

class MePrivacy extends Privacy
{
    public function validate(mixed $root, array $fieldArgs, mixed $queryContext = null, ?ResolveInfo $resolveInfo = null): bool
    {
        return $fieldArgs['id'] == Auth::id();
    }
}
