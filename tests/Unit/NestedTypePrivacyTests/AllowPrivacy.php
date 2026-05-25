<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\NestedTypePrivacyTests;

use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Privacy;

class AllowPrivacy extends Privacy
{
    /** @var list<array{root: mixed, args: array<string, mixed>, context: mixed, hasResolveInfo: bool}> */
    public static array $calls = [];

    /**
     * @param array<string, mixed> $fieldArgs
     */
    public function validate(mixed $root, array $fieldArgs, mixed $queryContext = null, ?ResolveInfo $resolveInfo = null): bool
    {
        self::$calls[] = [
            'root' => $root,
            'args' => $fieldArgs,
            'context' => $queryContext,
            'hasResolveInfo' => $resolveInfo instanceof ResolveInfo,
        ];

        return true;
    }
}
