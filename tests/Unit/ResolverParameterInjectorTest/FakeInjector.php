<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ResolverParameterInjectorTest;

use Rebing\GraphQL\Support\Contracts\ResolverParameterInjector;
use Rebing\GraphQL\Support\Field;

/**
 * Reusable test double for ResolverParameterInjector.
 *
 * Configurable: chooses which class names it `supports()` and what value to
 * `resolve()` for them. Tracks all calls for assertion.
 */
class FakeInjector implements ResolverParameterInjector
{
    /** @var list<string> */
    public array $supportsCalls = [];

    /** @var list<string> */
    public array $resolveCalls = [];

    /**
     * @param list<class-string> $supportedClasses
     */
    public function __construct(
        private readonly array $supportedClasses,
        private readonly mixed $value,
    ) {
    }

    public function supports(string $className): bool
    {
        $this->supportsCalls[] = $className;

        return \in_array($className, $this->supportedClasses, true);
    }

    public function resolve(string $className, array $arguments, array $fieldsAndArguments, Field $field): mixed
    {
        $this->resolveCalls[] = $className;

        return $this->value;
    }
}
