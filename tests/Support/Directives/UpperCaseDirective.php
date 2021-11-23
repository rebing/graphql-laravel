<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support\Directives;

use GraphQL\Language\DirectiveLocation;
use Rebing\GraphQL\Support\Directive;

class UpperCaseDirective extends Directive
{
    protected $attributes = [
        'name' => 'upper',
        'description' => 'The upper directive.',
    ];

    public function locations(): array
    {
        return [
            DirectiveLocation::FIELD,
        ];
    }

    public function handle($value, array $args = []): ?string
    {
        if (\is_string($value)) {
            return strtoupper($value);
        }

        return $value;
    }
}
