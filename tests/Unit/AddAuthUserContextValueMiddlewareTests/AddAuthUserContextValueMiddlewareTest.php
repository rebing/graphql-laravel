<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\AddAuthUserContextValueMiddlewareTests;

use Illuminate\Auth\GenericUser;
use Rebing\GraphQL\GraphQL;
use Rebing\GraphQL\Support\ExecutionMiddleware\AddAuthUserContextValueMiddleware;
use Rebing\GraphQL\Tests\TestCase;

/**
 * Behaviour tests for `AddAuthUserContextValueMiddleware` guard resolution.
 *
 * The middleware reads `graphql.schemas.<name>.group_attributes.guard` to
 * decide which auth guard's user is injected into the GraphQL context.
 * When that key is unset, the application's default guard is used.
 */
class AddAuthUserContextValueMiddlewareTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        // Register a second guard backed by the same provider; behavior is
        // identical to the default `web` guard, but it's a distinct guard
        // identity so we can verify the middleware addresses it correctly.
        $app['config']->set('auth.guards.api', [
            'driver' => 'session',
            'provider' => 'users',
        ]);

        $app['config']->set('graphql.execution_middleware', [
            AddAuthUserContextValueMiddleware::class,
        ]);

        $app['config']->set('graphql.schemas.web_schema', [
            'query' => ['returnAuthId' => ReturnAuthIdQuery::class],
        ]);

        $app['config']->set('graphql.schemas.api_schema', [
            'query' => ['returnAuthId' => ReturnAuthIdQuery::class],
            'group_attributes' => ['guard' => 'api'],
        ]);
    }

    public function testDefaultGuardIsUsedWhenSchemaHasNoGuardConfigured(): void
    {
        $this->actingAs(new GenericUser(['id' => 'web-user']));

        $result = $this->httpGraphql('{ returnAuthId }', ['schemaName' => 'web_schema']);

        self::assertSame(['data' => ['returnAuthId' => 'web-user']], $result);
    }

    public function testConfiguredGuardIsUsed(): void
    {
        $this->actingAs(new GenericUser(['id' => 'api-user']), 'api');

        $result = $this->httpGraphql('{ returnAuthId }', ['schemaName' => 'api_schema']);

        self::assertSame(['data' => ['returnAuthId' => 'api-user']], $result);
    }

    public function testSchemaGuardIsolatesContextFromDefaultGuardUser(): void
    {
        // Authenticate two different users on two different guards. Without
        // the per-schema guard lookup the middleware would call
        // `auth->guard()->user()` (the default guard); after the fix it
        // honours the schema's `group_attributes.guard`.
        $this->actingAs(new GenericUser(['id' => 'web-user']));
        $this->actingAs(new GenericUser(['id' => 'api-user']), 'api');

        $result = $this->httpGraphql('{ returnAuthId }', ['schemaName' => 'api_schema']);

        self::assertSame(['data' => ['returnAuthId' => 'api-user']], $result);
    }

    public function testNoAuthenticatedUserYieldsNullContext(): void
    {
        // No `actingAs` call: nobody is authenticated on either guard.
        $result = $this->httpGraphql('{ returnAuthId }', ['schemaName' => 'api_schema']);

        self::assertSame(['data' => ['returnAuthId' => 'no-user']], $result);
    }

    public function testCallerSuppliedContextIsNotOverridden(): void
    {
        // Programmatic API path: when the caller supplies a context value
        // explicitly, the middleware leaves it untouched. The
        // schema-level guard config is irrelevant in this case.
        $this->actingAs(new GenericUser(['id' => 'web-user']));

        $customUser = new GenericUser(['id' => 'custom-user']);
        /** @var GraphQL $graphql */
        $graphql = $this->app->make(GraphQL::class);

        $result = $graphql->query('{ returnAuthId }', null, [
            'schema' => 'web_schema',
            'context' => $customUser,
        ]);

        self::assertSame(['data' => ['returnAuthId' => 'custom-user']], $result);
    }

    public function testGlobalGroupAttributesGuardIsUsedAsFallback(): void
    {
        // When a schema has no `group_attributes.guard` of its own, the
        // middleware falls back to the global `route.group_attributes.guard`
        // (the same key Laravel route groups consume). This lets a
        // single-guard app set the guard once globally.
        $this->app['config']->set('graphql.route.group_attributes.guard', 'api');
        $this->actingAs(new GenericUser(['id' => 'api-user']), 'api');

        $result = $this->httpGraphql('{ returnAuthId }', ['schemaName' => 'web_schema']);

        self::assertSame(['data' => ['returnAuthId' => 'api-user']], $result);
    }

    public function testPerSchemaGuardOverridesGlobalGroupAttributesGuard(): void
    {
        // Per-schema config wins when both are present.
        $this->app['config']->set('graphql.route.group_attributes.guard', 'web');
        $this->actingAs(new GenericUser(['id' => 'web-user']));
        $this->actingAs(new GenericUser(['id' => 'api-user']), 'api');

        $result = $this->httpGraphql('{ returnAuthId }', ['schemaName' => 'api_schema']);

        self::assertSame(['data' => ['returnAuthId' => 'api-user']], $result);
    }
}
