<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support;

abstract class Directive extends \GraphQL\Type\Definition\Directive
{
    /** @var array<string, string> */
    protected $attributes = [];

    public function __construct()
    {
        $config = [
            'locations' => $this->locations(),
            'args' => $this->args(),
        ];

        parent::__construct(array_merge($this->attributes, $config));
    }

    /**
     * Specify the arguments for this directive.
     *
     * @return array<array<string, mixed>>
     */
    public function args(): array
    {
        return [];
    }

    /**
     * Specify the locations where this directive can be applied.
     *
     * @return array<string>
     */
    abstract public function locations(): array;

    /**
     * @param mixed $value
     * @param array<string, mixed> $args
     *
     * @return mixed
     */
    abstract public function handle($value, array $args = []);
}
