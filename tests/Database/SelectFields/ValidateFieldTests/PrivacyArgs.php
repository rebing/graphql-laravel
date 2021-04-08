<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\ValidateFieldTests;

use PHPUnit\Framework\Assert;
use Rebing\GraphQL\Support\Privacy;

class PrivacyArgs extends Privacy
{
    /**
     * @inheritDoc
     */
    public function validate(array $queryArgs, $queryContext = null): bool
    {
        $expectedQueryArgs = [
            'arg_from_query' => true,
        ];
        Assert::assertSame($expectedQueryArgs, $queryArgs);

        return true;
    }
}
