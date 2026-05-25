<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ResolverParameterInjectorTest;

/**
 * Plain service used to verify resolver parameter injection delivers an
 * injector-provided instance rather than an `app()->make()` resolution.
 */
class InjectableService
{
    public function __construct(public readonly string $marker = 'default')
    {
    }
}
