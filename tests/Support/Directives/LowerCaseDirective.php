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

    /** @var LowerCaseDirective|null */
    private static $instance = null;

    /**
     * UpperCaseDirective constructor.
     */
    protected function __construct()
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
     * @return LowerCaseDirective
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
            $value = strtolower($value);
        }

        return $value;
    }
}
