<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ResolverParameterInjectorTest;

use InvalidArgumentException;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Field;
use Rebing\GraphQL\Tests\TestCase;

/**
 * Covers `Rebing\GraphQL\Support\Contracts\ResolverParameterInjector`,
 * `Field::registerParameterInjector()` and `Field::clearParameterInjectors()`,
 * the public extension API used by external packages such as
 * `rebing/graphql-laravel-select-fields` to inject custom DI parameters into
 * resolver methods.
 */
class ResolverParameterInjectorTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('graphql.schemas.default', [
            'query' => [
                QueryWithInjectedService::class,
                QueryWithBuiltInTypedParam::class,
            ],
        ]);
    }

    protected function tearDown(): void
    {
        // Static state must be reset to avoid bleed between tests.
        Field::clearParameterInjectors();

        parent::tearDown();
    }

    public function testRegisteredInjectorReceivesAndProvidesParameter(): void
    {
        $injectedValue = new InjectableService('from-injector');
        $injector = new FakeInjector([InjectableService::class], $injectedValue);

        Field::registerParameterInjector($injector);

        $result = GraphQL::queryAndReturnResult('{ queryWithInjectedService }');

        self::assertEmpty($result->errors);
        self::assertSame(['queryWithInjectedService' => 'from-injector'], $result->data);

        // The injector must have been asked about, and resolved, exactly the requested type.
        self::assertContains(InjectableService::class, $injector->supportsCalls);
        self::assertSame([InjectableService::class], $injector->resolveCalls);
    }

    public function testFirstMatchingInjectorWins(): void
    {
        $first = new FakeInjector([InjectableService::class], new InjectableService('first'));
        $second = new FakeInjector([InjectableService::class], new InjectableService('second'));

        Field::registerParameterInjector($first);
        Field::registerParameterInjector($second);

        $result = GraphQL::queryAndReturnResult('{ queryWithInjectedService }');

        self::assertEmpty($result->errors);
        self::assertSame(['queryWithInjectedService' => 'first'], $result->data);

        // Second injector is never asked because the first already supported the type.
        self::assertSame([InjectableService::class], $first->supportsCalls);
        self::assertSame([], $second->supportsCalls);
        self::assertSame([InjectableService::class], $first->resolveCalls);
        self::assertSame([], $second->resolveCalls);
    }

    public function testInjectorThatDoesNotSupportFallsThroughToContainer(): void
    {
        $rejecting = new FakeInjector([], new InjectableService('not-used'));
        Field::registerParameterInjector($rejecting);

        // Bind the service in the container so that the fallback resolution succeeds.
        $this->app->instance(InjectableService::class, new InjectableService('from-container'));

        $result = GraphQL::queryAndReturnResult('{ queryWithInjectedService }');

        self::assertEmpty($result->errors);
        self::assertSame(['queryWithInjectedService' => 'from-container'], $result->data);

        // Injector was queried but returned `false`, so resolve() was never called.
        self::assertSame([InjectableService::class], $rejecting->supportsCalls);
        self::assertSame([], $rejecting->resolveCalls);
    }

    public function testClearParameterInjectorsRemovesAll(): void
    {
        $injector = new FakeInjector([InjectableService::class], new InjectableService('via-injector'));
        Field::registerParameterInjector($injector);

        Field::clearParameterInjectors();

        // After clearing, the resolver should fall back to the container.
        $this->app->instance(InjectableService::class, new InjectableService('via-container-after-clear'));

        $result = GraphQL::queryAndReturnResult('{ queryWithInjectedService }');

        self::assertEmpty($result->errors);
        self::assertSame(['queryWithInjectedService' => 'via-container-after-clear'], $result->data);

        // Cleared injector was never asked.
        self::assertSame([], $injector->supportsCalls);
    }

    public function testBuiltInTypedParameterCannotBeInjected(): void
    {
        // Built-in types like `string` are not class-resolvable, so the
        // resolver-DI machinery throws InvalidArgumentException at setup.
        $result = GraphQL::queryAndReturnResult('{ queryWithBuiltInTypedParam }');

        self::assertNotEmpty($result->errors);

        $previous = $result->errors[0]->getPrevious();
        self::assertInstanceOf(InvalidArgumentException::class, $previous);
        self::assertStringContainsString('cannotInject', $previous->getMessage());
    }
}
