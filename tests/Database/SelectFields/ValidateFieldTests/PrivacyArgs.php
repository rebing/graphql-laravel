<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\ValidateFieldTests;

use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\Assert;
use Rebing\GraphQL\Support\Privacy;

class PrivacyArgs extends Privacy
{
    /**
     * @inheritDoc
     */
    public function validate(mixed $root, array $fieldArgs, mixed $queryContext = null, ?ResolveInfo $resolveInfo = null): bool
    {
        $expectedQueryArgs = [
            'arg_from_field' => true,
        ];
        Assert::assertSame($expectedQueryArgs, $fieldArgs);

        return true;
    }
}
