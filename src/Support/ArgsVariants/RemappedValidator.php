<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\ArgsVariants;

use Illuminate\Contracts\Support\MessageBag as MessageBagContract;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

/**
 * Decorates a validator so that error keys have their
 * '.argsVariants.<hash>' segments stripped, keeping the historical
 * 'path.args.argName' format in user-facing validation output.
 */
final class RemappedValidator implements ValidatorContract
{
    public function __construct(
        private readonly ValidatorContract $inner,
        private readonly MessageBagContract $remapped,
    ) {
    }

    public function errors(): MessageBagContract
    {
        return $this->remapped;
    }

    public function getMessageBag(): MessageBagContract
    {
        return $this->remapped;
    }

    public function fails(): bool
    {
        return $this->inner->fails();
    }

    /** @return array<string,mixed> */
    public function failed(): array
    {
        return $this->inner->failed();
    }

    /** @return array<string,mixed> */
    public function validate(): array
    {
        return $this->inner->validate();
    }

    /** @return array<string,mixed> */
    public function validated(): array
    {
        return $this->inner->validated();
    }

    /**
     * @param string|array<string> $attribute
     * @param string|array<mixed> $rules
     */
    public function sometimes($attribute, $rules, callable $callback): static
    {
        $this->inner->sometimes($attribute, $rules, $callback);

        return $this;
    }

    /** @param callable|string $callback */
    public function after($callback): static
    {
        $this->inner->after($callback);

        return $this;
    }

    /**
     * The contract methods above are frozen against the Laravel 12/13
     * Illuminate\Contracts\Validation\Validator interface. Concrete
     * validators expose more methods (e.g. setAttributeNames, sometimes
     * callers downcast) — delegate anything else to the inner instance.
     *
     * @param array<int,mixed> $arguments
     */
    public function __call(string $method, array $arguments): mixed
    {
        return $this->inner->{$method}(...$arguments);
    }
}
