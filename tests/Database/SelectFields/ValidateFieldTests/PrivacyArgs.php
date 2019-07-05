<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\ValidateFieldTests;

use PHPUnit\Framework\Assert;
use Rebing\GraphQL\Support\Privacy;

class PrivacyArgs extends Privacy
{
    /**
     * @param  array  $queryArgs  Arguments given with the query/mutation
     * @return bool Return `true` to allow access to the field in question,
     *   `false otherwise
     */
    public function validate(array $queryArgs): bool
    {
        $expectedQueryArgs = [
            'arg_from_query' => true,
        ];
        Assert::assertSame($expectedQueryArgs, $queryArgs);

        return true;
    }
}
