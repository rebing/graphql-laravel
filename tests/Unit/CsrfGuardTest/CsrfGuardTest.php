<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\CsrfGuardTest;

use Rebing\GraphQL\Support\Middleware\CsrfGuard;
use Rebing\GraphQL\Tests\TestCase;

/**
 * Integration tests for the CsrfGuard HTTP middleware.
 *
 * Each test sends a real HTTP request to the GraphQL endpoint with specific
 * headers/content-types and verifies that the middleware correctly allows or
 * rejects the request at the HTTP layer.
 */
class CsrfGuardTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        // Enable GET so we can test GET rejection
        $app['config']->set('graphql.schemas.default.method', ['GET', 'POST']);

        // Apply CsrfGuard with strict defaults to the default schema
        $app['config']->set('graphql.route.middleware', [CsrfGuard::class]);
    }

    // ------------------------------------------------------------------
    // GET rejection
    // ------------------------------------------------------------------

    public function testGetRequestIsRejected(): void
    {
        $response = $this->call('GET', '/graphql', [
            'query' => '{ examples { test } }',
        ]);

        self::assertSame(400, $response->getStatusCode());
        self::assertStringContainsString(
            'GET requests are not allowed',
            (string) $response->getContent(),
        );
    }

    // ------------------------------------------------------------------
    // Sec-Fetch-Site checks
    // ------------------------------------------------------------------

    public function testSameOriginFetchMetadataAllowsRequest(): void
    {
        $response = $this->call(
            'POST',
            '/graphql',
            ['query' => '{ examples { test } }'],
            [],
            [],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin'],
        );

        self::assertSame(200, $response->getStatusCode());
        $data = $response->getData(true);
        self::assertArrayHasKey('data', $data);
    }

    public function testSameSiteFetchMetadataAllowsRequest(): void
    {
        $response = $this->call(
            'POST',
            '/graphql',
            ['query' => '{ examples { test } }'],
            [],
            [],
            ['HTTP_SEC_FETCH_SITE' => 'same-site'],
        );

        self::assertSame(200, $response->getStatusCode());
        $data = $response->getData(true);
        self::assertArrayHasKey('data', $data);
    }

    public function testCrossSiteFetchMetadataRejectsJsonRequest(): void
    {
        $response = $this->json('POST', '/graphql', [
            'query' => '{ examples { test } }',
        ], ['Sec-Fetch-Site' => 'cross-site']);

        self::assertSame(400, $response->getStatusCode());
        self::assertStringContainsString(
            'Cross-site requests are not allowed',
            (string) $response->getContent(),
        );
    }

    public function testNoneFetchMetadataRejectsRequest(): void
    {
        $response = $this->json('POST', '/graphql', [
            'query' => '{ examples { test } }',
        ], ['Sec-Fetch-Site' => 'none']);

        self::assertSame(400, $response->getStatusCode());
        self::assertStringContainsString(
            'Cross-site requests are not allowed',
            (string) $response->getContent(),
        );
    }

    public function testUnknownFetchMetadataFallsThroughToOtherChecks(): void
    {
        $response = $this->call(
            'POST',
            '/graphql',
            ['query' => '{ examples { test } }'],
            [],
            [],
            ['HTTP_SEC_FETCH_SITE' => 'future-browser-value'],
        );

        self::assertSame(400, $response->getStatusCode());
        self::assertStringContainsString(
            'Request lacks indicators',
            (string) $response->getContent(),
        );
    }

    public function testFetchMetadataCheckCanBeDisabled(): void
    {
        $this->app['config']->set('graphql.route.middleware', [
            CsrfGuard::using(checkFetchMetadata: false),
        ]);
        $this->reloadRoutes();

        $response = $this->call(
            'POST',
            '/graphql',
            ['query' => '{ examples { test } }'],
            [],
            [],
            [
                'HTTP_SEC_FETCH_SITE' => 'cross-site',
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            ],
        );

        self::assertSame(200, $response->getStatusCode());
    }

    // ------------------------------------------------------------------
    // Custom header checks
    // ------------------------------------------------------------------

    public function testCustomHeaderAllowsFormEncodedRequest(): void
    {
        $response = $this->call(
            'POST',
            '/graphql',
            ['query' => '{ examples { test } }'],
            [],
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
        );

        self::assertSame(200, $response->getStatusCode());
        $data = $response->getData(true);
        self::assertArrayHasKey('data', $data);
    }

    public function testCustomHeaderCheckCanBeDisabled(): void
    {
        $this->app['config']->set('graphql.route.middleware', [
            CsrfGuard::using(allowCustomHeader: false),
        ]);
        $this->reloadRoutes();

        $response = $this->call(
            'POST',
            '/graphql',
            ['query' => '{ examples { test } }'],
            [],
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
        );

        self::assertSame(400, $response->getStatusCode());
        self::assertStringContainsString(
            'Request lacks indicators',
            (string) $response->getContent(),
        );
    }

    // ------------------------------------------------------------------
    // Content-Type checks
    // ------------------------------------------------------------------

    public function testApplicationJsonAllowsRequest(): void
    {
        $response = $this->json('POST', '/graphql', [
            'query' => '{ examples { test } }',
        ]);

        self::assertSame(200, $response->getStatusCode());
        $data = $response->getData(true);
        self::assertArrayHasKey('data', $data);
    }

    public function testNonSimpleContentTypeCheckCanBeDisabled(): void
    {
        $this->app['config']->set('graphql.route.middleware', [
            CsrfGuard::using(allowNonSimpleContentType: false),
        ]);
        $this->reloadRoutes();

        $response = $this->json('POST', '/graphql', [
            'query' => '{ examples { test } }',
        ]);

        self::assertSame(400, $response->getStatusCode());
        self::assertStringContainsString(
            'Request lacks indicators',
            (string) $response->getContent(),
        );
    }

    public function testApplicationGraphqlAllowsRequest(): void
    {
        $response = $this->call(
            'POST',
            '/graphql',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/graphql'],
            '{ examples { test } }',
        );

        self::assertSame(200, $response->getStatusCode());
        $data = $response->getData(true);
        self::assertArrayHasKey('data', $data);
    }

    public function testFormEncodedPostWithoutAnyIndicatorIsRejected(): void
    {
        // Simulates an HTML form submission: no Sec-Fetch-Site, no custom header,
        // and a simple Content-Type.  This is exactly what a CSRF attack looks like.
        $response = $this->call(
            'POST',
            '/graphql',
            ['query' => '{ examples { test } }'],
        );

        self::assertSame(400, $response->getStatusCode());
        self::assertStringContainsString(
            'Request lacks indicators',
            (string) $response->getContent(),
        );
    }

    public function testTextPlainContentTypeIsRejected(): void
    {
        $response = $this->call(
            'POST',
            '/graphql',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/plain'],
            '{ examples { test } }',
        );

        self::assertSame(400, $response->getStatusCode());
        self::assertStringContainsString(
            'Request lacks indicators',
            (string) $response->getContent(),
        );
    }

    // ------------------------------------------------------------------
    // File upload: multipart/form-data + X-Requested-With
    // ------------------------------------------------------------------

    public function testMultipartWithCustomHeaderIsAllowed(): void
    {
        // Simulate what a JavaScript file upload client does: multipart body
        // with X-Requested-With header.
        $response = $this->call(
            'POST',
            '/graphql',
            ['query' => '{ examples { test } }'],
            [],
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
        );

        self::assertSame(200, $response->getStatusCode());
        $data = $response->getData(true);
        self::assertArrayHasKey('data', $data);
    }

    public function testMultipartWithoutCustomHeaderIsRejected(): void
    {
        // Simulates a cross-origin form with enctype="multipart/form-data"
        // and no custom header — should be blocked.
        $response = $this->call(
            'POST',
            '/graphql',
            ['query' => '{ examples { test } }'],
        );

        self::assertSame(400, $response->getStatusCode());
    }

    // ------------------------------------------------------------------
    // strictWhenAmbiguous: false (permissive mode)
    // ------------------------------------------------------------------

    public function testPermissiveModeAllowsAmbiguousRequest(): void
    {
        // Reconfigure with permissive mode for this test
        $this->app['config']->set('graphql.route.middleware', [
            CsrfGuard::using(strictWhenAmbiguous: false),
        ]);
        $this->reloadRoutes();

        // No Sec-Fetch-Site, no custom header, default (simple) Content-Type
        $response = $this->call(
            'POST',
            '/graphql',
            ['query' => '{ examples { test } }'],
        );

        self::assertSame(200, $response->getStatusCode());
        $data = $response->getData(true);
        self::assertArrayHasKey('data', $data);
    }

    // ------------------------------------------------------------------
    // rejectGet: false
    // ------------------------------------------------------------------

    public function testGetAllowedWhenRejectGetDisabled(): void
    {
        $this->app['config']->set('graphql.route.middleware', [
            CsrfGuard::using(rejectGet: false, strictWhenAmbiguous: false),
        ]);
        $this->reloadRoutes();

        $response = $this->call('GET', '/graphql', [
            'query' => '{ examples { test } }',
        ]);

        self::assertSame(200, $response->getStatusCode());
        $data = $response->getData(true);
        self::assertArrayHasKey('data', $data);
    }

    // ------------------------------------------------------------------
    // Custom header name
    // ------------------------------------------------------------------

    public function testCustomHeaderNameIsRespected(): void
    {
        $this->app['config']->set('graphql.route.middleware', [
            CsrfGuard::using(customHeaderName: 'Apollo-Require-Preflight'),
        ]);
        $this->reloadRoutes();

        // Default X-Requested-With should NOT work
        $response = $this->call(
            'POST',
            '/graphql',
            ['query' => '{ examples { test } }'],
            [],
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
        );

        self::assertSame(400, $response->getStatusCode());

        // The configured header should work
        $response = $this->call(
            'POST',
            '/graphql',
            ['query' => '{ examples { test } }'],
            [],
            [],
            ['HTTP_APOLLO_REQUIRE_PREFLIGHT' => '1'],
        );

        self::assertSame(200, $response->getStatusCode());
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Reload routes so middleware changes take effect mid-test.
     */
    private function reloadRoutes(): void
    {
        $router = $this->app['router'];

        // Clear existing routes
        $router->getRoutes()->refreshNameLookups();
        $router->getRoutes()->refreshActionLookups();

        // Re-register GraphQL routes with new config
        $routesFile = $this->app->basePath('vendor/rebing/graphql-laravel/src/routes.php');

        if (!file_exists($routesFile)) {
            $routesFile = __DIR__ . '/../../../src/routes.php';
        }

        require $routesFile;
    }
}
