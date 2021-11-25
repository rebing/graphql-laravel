<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support\Directives;

use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Directive;

class TrimDirective extends Directive
{
    /** @var array<string, string> */
    protected $attributes = [
        'name' => 'trim',
        'description' => 'The trim directive.',
    ];

    /**
     * @return array<string>
     */
    public function locations(): array
    {
        return [
            DirectiveLocation::FIELD,
        ];
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function args(): array
    {
        return [
            'chars' => [
                'type' => Type::string(),
                'description' => 'Trim field by given characters.',
            ],
        ];
    }

    /**
     * @param array<mixed> $args
     */
    public function handle($value, array $args = []): string
    {
        if (isset($args['chars'])) {
            return trim($value, $args['chars']);
        }

        return trim($value);
    }
}
