<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support\Directives;

use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Directive;

class TrimDirective extends Directive
{
    protected $attributes = [
        'name' => 'trim',
        'description' => 'The trim directive.',
    ];

    public function locations(): array
    {
        return [
            DirectiveLocation::FIELD,
        ];
    }

    public function args(): array
    {
        return [
            'chars' => [
                'type' => Type::string(),
                'description' => 'Trim field by given characters.',
            ],
        ];
    }

    public function handle($value, array $args = []): string
    {
        if (isset($args['chars'])) {
            return trim($value, $args['chars']);
        }

        return trim($value);
    }
}
