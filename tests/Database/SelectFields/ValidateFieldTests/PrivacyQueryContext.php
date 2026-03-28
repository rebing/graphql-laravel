<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\ValidateFieldTests;

use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\Assert;
use Rebing\GraphQL\Support\Privacy;

class PrivacyQueryContext extends Privacy
{
    /**
     * @inheritDoc
     */
    public function validate(mixed $root, array $fieldArgs, mixed $queryContext = null, ?ResolveInfo $resolveInfo = null): bool
    {
        $expectedQueryContext = [
            'arg_from_context_true' => true,
            'arg_from_context_false' => false,
        ];
        Assert::assertSame($expectedQueryContext, $queryContext);

        return true;
    }
}
