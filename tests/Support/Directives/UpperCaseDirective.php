<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Support\Directives;

use GraphQL\Language\DirectiveLocation;

/**
 * Class UpperCaseDirective.
 */
class UpperCaseDirective extends \Rebing\GraphQL\Support\Directive
{
    /** @var string */
    const NAME = 'upper';

    /** @var UpperCaseDirective|null */
    private static $instance = null;

    /**
     * UpperCaseDirective constructor.
     */
    protected function __construct()
    {
        parent::__construct([
            'name' => static::NAME,
            'description' => 'The upper directive.',
            'locations' => [
                DirectiveLocation::FIELD,
            ],
            'args' => [],
        ]);
    }

    /**
     * @return UpperCaseDirective
     */
    public static function getInstance(): self
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param mixed $value
     * @param  array<mixed>  $args
     * @return string|null
     */
    public function handle($value, array $args = []): ?string
    {
        if (! empty($value)) {
            $value = strtoupper($value);
        }

        return $value;
    }
}
