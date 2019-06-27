<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\MutationCustomRulesTests;

use Illuminate\Contracts\Validation\Rule;

class RuleObject implements Rule
{
    public function passes($attribute, $value)
    {
        return false;
    }

    public function message()
    {
        return 'arg1 is invalid';
    }
}
