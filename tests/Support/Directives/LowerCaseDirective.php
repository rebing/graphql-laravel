<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Support\Directives;

use GraphQL\Language\DirectiveLocation;

/**
 * Class LowerCaseDirective.
 */
class LowerCaseDirective extends \Rebing\GraphQL\Support\Directive
{
    /** @var string */
    const NAME = 'lower';

    /**
     * LowerCaseDirective constructor.
     */
    public function __construct()
    {
        parent::__construct([
            'name' => static::NAME,
            'description' => 'The lower directive.',
            'locations' => [
                DirectiveLocation::FIELD,
            ],
            'args' => [],
        ]);
    }

    /**
     * @param mixed $value
     * @param  array<mixed>  $args
     * @return string|null
     */
    public function handle($value, array $args = []): ?string
    {
        if (! empty($value)) {
            $value = strtolower($value);
        }

        return $value;
    }
}
