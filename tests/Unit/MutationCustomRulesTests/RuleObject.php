<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\MutationCustomRulesTests;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RuleObject implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $fail('arg1 is invalid');
    }
}
