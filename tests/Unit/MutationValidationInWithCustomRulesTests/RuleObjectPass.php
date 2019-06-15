<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\MutationValidationInWithCustomRulesTests;

use Illuminate\Contracts\Validation\Rule;

class RuleObjectPass implements Rule
{
    public function passes($attribute, $value)
    {
        return true;
    }

    public function message()
    {
        return 'this message is not expected to be triggered';
    }
}
