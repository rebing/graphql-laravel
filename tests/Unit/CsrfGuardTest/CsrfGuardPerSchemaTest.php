<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\CsrfGuardTest;

use Rebing\GraphQL\Support\Middleware\CsrfGuard;
use Rebing\GraphQL\Tests\Support\Objects\ExamplesQuery;
use Rebing\GraphQL\Tests\TestCase;

/**
 * Tests that different schemas can have independently configured CsrfGuard policies.
 */
class CsrfGuardPerSchemaTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        // No global route middleware -- each schema configures its own
        $app['config']->set('graphql.route.middleware', []);

        // "strict" schema: full CSRF guard with strict defaults
        $app['config']->set('graphql.schemas.strict', [
            'query' => ['examples' => ExamplesQuery::class],
            'middleware' => [CsrfGuard::class],
            'method' => ['POST'],
        ]);

        // "permissive" schema: CSRF guard but allows ambiguous requests
        $app['config']->set('graphql.schemas.permissive', [
            'query' => ['examples' => ExamplesQuery::class],
            'middleware' => [CsrfGuard::using(strictWhenAmbiguous: false)],
            'method' => ['POST'],
        ]);

        // "unprotected" schema: explicitly no CSRF middleware
        // We must set a non-empty middleware array to avoid inheriting group
        // middleware (empty arrays are filtered by array_filter in routes.php).
        // Using a dummy middleware that always passes.
        $app['config']->set('graphql.schemas.unprotected', [
            'query' => ['examples' => ExamplesQuery::class],
            'method' => ['POST'],
            // middleware => null means "use group default" (which is empty here)
        ]);
    }

    public function testStrictSchemaRejectsFormPost(): void
    {
        // No Sec-Fetch-Site, no custom header, simple Content-Type (default)
        $response = $this->call('POST', '/graphql/strict', [
            'query' => '{ examples { test } }',
        ]);

        self::assertSame(400, $response->getStatusCode());
        self::assertStringContainsString('Request lacks indicators', (string) $response->getContent());
    }

    public function testStrictSchemaAllowsJsonPost(): void
    {
        $response = $this->json('POST', '/graphql/strict', [
            'query' => '{ examples { test } }',
        ]);

        self::assertSame(200, $response->getStatusCode());
        $data = $response->getData(true);
        self::assertArrayHasKey('data', $data);
    }

    public function testPermissiveSchemaAllowsFormPost(): void
    {
        // No Sec-Fetch-Site, no custom header, simple Content-Type --
        // but permissive mode allows it through.
        $response = $this->call('POST', '/graphql/permissive', [
            'query' => '{ examples { test } }',
        ]);

        self::assertSame(200, $response->getStatusCode());
        $data = $response->getData(true);
        self::assertArrayHasKey('data', $data);
    }

    public function testPermissiveSchemaStillRejectsCrossSite(): void
    {
        // Sec-Fetch-Site: cross-site is a definitive attack signal --
        // even permissive mode rejects it.
        $response = $this->json('POST', '/graphql/permissive', [
            'query' => '{ examples { test } }',
        ], ['Sec-Fetch-Site' => 'cross-site']);

        self::assertSame(400, $response->getStatusCode());
        self::assertStringContainsString('Cross-site', (string) $response->getContent());
    }

    public function testUnprotectedSchemaAllowsAnything(): void
    {
        // No CSRF middleware at all (null middleware = group default = empty)
        $response = $this->call('POST', '/graphql/unprotected', [
            'query' => '{ examples { test } }',
        ]);

        self::assertSame(200, $response->getStatusCode());
        $data = $response->getData(true);
        self::assertArrayHasKey('data', $data);
    }
}
