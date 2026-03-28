<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\ValidateFieldTests;

use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Privacy;

class PrivacyDenied extends Privacy
{
    /**
     * @inheritDoc
     */
    public function validate(mixed $root, array $fieldArgs, mixed $queryContext = null, ?ResolveInfo $resolveInfo = null): bool
    {
        return false;
    }
}
