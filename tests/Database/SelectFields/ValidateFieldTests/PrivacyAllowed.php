<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\SelectFields\ValidateFieldTests;

use Rebing\GraphQL\Support\Privacy;

class PrivacyAllowed extends Privacy
{
    /**
     * @inheritDoc
     */
    public function validate(array $queryArgs, $queryContext = null): bool
    {
        return true;
    }
}
