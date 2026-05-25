<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\NestedTypePrivacyTests;

use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Privacy;

class DenyPrivacy extends Privacy
{
    public static int $calls = 0;

    /**
     * @param array<string, mixed> $fieldArgs
     */
    public function validate(mixed $root, array $fieldArgs, mixed $queryContext = null, ?ResolveInfo $resolveInfo = null): bool
    {
        self::$calls++;

        return false;
    }
}
