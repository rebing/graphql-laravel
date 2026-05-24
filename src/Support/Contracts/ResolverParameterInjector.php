<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\Contracts;

use Rebing\GraphQL\Support\Field;

/**
 * Extension point for injecting custom parameters into Field resolver methods.
 *
 * When a resolver method (e.g. `resolve()` on a Query or Mutation) declares
 * type-hinted parameters beyond the standard three ($root, $args, $context),
 * the library uses registered injectors to resolve them.
 *
 * External packages can implement this interface and register their injector
 * via {@see Field::registerParameterInjector()} to provide custom DI into
 * resolver methods.
 */
interface ResolverParameterInjector
{
    /**
     * Can this injector resolve a parameter with the given type-hint?
     *
     * @param string $className The fully-qualified class name from the type-hint
     */
    public function supports(string $className): bool;

    /**
     * Resolve the parameter value.
     *
     * @param string $className The fully-qualified class name from the type-hint
     * @param array<int,mixed> $arguments The 4 standard resolver arguments [root, args, ctx, resolveInfo]
     * @param array<string,mixed> $fieldsAndArguments The query plan from ResolveInfo::lookAhead()->queryPlan()
     * @param Field $field The Field/Query/Mutation instance owning the resolver
     */
    public function resolve(string $className, array $arguments, array $fieldsAndArguments, Field $field): mixed;
}
