<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\MutationValidationInWithCustomRulesTests;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RuleObjectPass implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
    }
}
