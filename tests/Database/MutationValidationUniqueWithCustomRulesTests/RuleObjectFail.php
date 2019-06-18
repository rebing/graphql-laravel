<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database\MutationValidationUniqueWithCustomRulesTests;

use Illuminate\Contracts\Validation\Rule;

class RuleObjectFail implements Rule
{
    public function passes($attribute, $value)
    {
        return false;
    }

    public function message()
    {
        return 'rule object validation fails';
    }
}
