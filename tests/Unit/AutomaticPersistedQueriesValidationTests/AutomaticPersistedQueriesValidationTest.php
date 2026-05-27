<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\AutomaticPersistedQueriesValidationTests;

use GraphQL\Type\Introspection;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\QueryDepth;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Rebing\GraphQL\Tests\TestCase;

/**
 * Verifies that APQ caches only validated queries.
 */
class AutomaticPersistedQueriesValidationTest extends TestCase
{
    private const APQ_CACHE_DRIVER = 'array';
    private const APQ_CACHE_PREFIX = 'apq-test';

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.apq.enable', true);
        $app['config']->set('graphql.apq.cache_driver', self::APQ_CACHE_DRIVER);
        $app['config']->set('graphql.apq.cache_prefix', self::APQ_CACHE_PREFIX);

        // Register a recursive `Tree` type plus a `tree` query so depth tests
        // can craft queries of precise nested-selection-set depth without
        // depending on the shape of unrelated fixtures.
        $defaultSchema = $app['config']->get('graphql.schemas.default');
        $defaultSchema['query']['tree'] = DeepTreeQuery::class;
        $app['config']->set('graphql.schemas.default', $defaultSchema);

        $types = $app['config']->get('graphql.types', []);
        $types['Tree'] = TreeType::class;
        $app['config']->set('graphql.types', $types);
    }

    public function testValidQueryIsCached(): void
    {
        $query = trim($this->queries['examples']);
        $hash = hash('sha256', $query);

        // Negotiation: send query + hash; expect a successful response and a
        // cache entry written.
        $response = $this->json('POST', '/graphql', [
            'query' => $query,
            'extensions' => [
                'persistedQuery' => ['version' => 1, 'sha256Hash' => $hash],
            ],
        ]);

        self::assertEquals(200, $response->getStatusCode());
        self::assertArrayHasKey('data', $response->json());
        self::assertTrue($this->apqCacheHas('default', $hash));

        // Hash-only replay: the cached entry serves the response.
        $response = $this->json('POST', '/graphql', [
            'extensions' => [
                'persistedQuery' => ['version' => 1, 'sha256Hash' => $hash],
            ],
        ]);

        self::assertEquals(200, $response->getStatusCode());
        $content = $response->json();
        self::assertArrayHasKey('data', $content);
        self::assertEquals(['examples' => $this->data], $content['data']);
    }

    public function testQueryExceedingMaxDepthIsRejectedAndNotCached(): void
    {
        // The `tree { child { child { child { name } } } }` query nests three
        // fields-with-selection-sets (webonyx counts depth as the deepest
        // field-with-selection-set; leaves don't count). max_depth = 2 fails
        // it and the cache must remain empty.
        $this->app['config']->set('graphql.security.query_max_depth', 2);

        $query = <<<'GRAPHQL'
query DeepTree {
  tree {
    child {
      child {
        child {
          name
        }
      }
    }
  }
}
GRAPHQL;
        $hash = hash('sha256', $query);

        $response = $this->json('POST', '/graphql', [
            'query' => $query,
            'extensions' => [
                'persistedQuery' => ['version' => 1, 'sha256Hash' => $hash],
            ],
        ]);

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->json();
        self::assertArrayHasKey('errors', $content);
        self::assertArrayNotHasKey('data', $content);
        self::assertStringContainsString(
            'Max query depth should be 2',
            $content['errors'][0]['message'],
        );

        self::assertFalse($this->apqCacheHas('default', $hash));
    }

    public function testQueryExceedingMaxComplexityIsRejectedAndNotCached(): void
    {
        // The cumulative complexity of `examples { test }` exceeds 1.
        $this->app['config']->set('graphql.security.query_max_complexity', 1);

        $query = trim($this->queries['examples']);
        $hash = hash('sha256', $query);

        $response = $this->json('POST', '/graphql', [
            'query' => $query,
            'extensions' => [
                'persistedQuery' => ['version' => 1, 'sha256Hash' => $hash],
            ],
        ]);

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->json();
        self::assertArrayHasKey('errors', $content);
        self::assertArrayNotHasKey('data', $content);
        self::assertStringContainsString(
            'Max query complexity should be 1',
            $content['errors'][0]['message'],
        );

        self::assertFalse($this->apqCacheHas('default', $hash));
    }

    public function testIntrospectionQueryIsRejectedAndNotCachedWhenDisabled(): void
    {
        $this->app['config']->set('graphql.security.disable_introspection', true);

        // `Introspection::getIntrospectionQuery()` is large; the runtime
        // RequestParser may trim whitespace before APQ sees it, so trim
        // here too to keep both sides of the hash check consistent.
        $query = trim(Introspection::getIntrospectionQuery());
        $hash = hash('sha256', $query);

        $response = $this->json('POST', '/graphql', [
            'query' => $query,
            'extensions' => [
                'persistedQuery' => ['version' => 1, 'sha256Hash' => $hash],
            ],
        ]);

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->json();
        self::assertArrayHasKey('errors', $content);
        self::assertStringContainsString(
            'GraphQL introspection is not allowed',
            $content['errors'][0]['message'],
        );

        self::assertFalse($this->apqCacheHas('default', $hash));
    }

    public function testRetrievedCachedQueryIsReValidated(): void
    {
        // Cache a query under permissive limits, then re-issue the request
        // (hash-only) under tightened limits. Validation runs in
        // `GraphqlExecutionMiddleware` regardless of how the document arrived,
        // so the previously-acceptable cached query now fails.
        $query = <<<'GRAPHQL'
query DeepTree {
  tree {
    child {
      child {
        child {
          name
        }
      }
    }
  }
}
GRAPHQL;
        $hash = hash('sha256', $query);

        $response = $this->json('POST', '/graphql', [
            'query' => $query,
            'extensions' => [
                'persistedQuery' => ['version' => 1, 'sha256Hash' => $hash],
            ],
        ]);
        self::assertEquals(200, $response->getStatusCode());
        self::assertArrayHasKey('data', $response->json());
        self::assertTrue($this->apqCacheHas('default', $hash));

        // Tighten limits *after* the cache entry was written. Validation rules
        // are registered globally at service-provider boot, so updating
        // `graphql.security.*` config mid-request has no effect; mutate the
        // shared rule instance directly.
        /** @var QueryDepth $queryDepth */
        $queryDepth = DocumentValidator::getRule(QueryDepth::class);
        $queryDepth->setMaxQueryDepth(2);

        $response = $this->json('POST', '/graphql', [
            'extensions' => [
                'persistedQuery' => ['version' => 1, 'sha256Hash' => $hash],
            ],
        ]);

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->json();
        self::assertArrayHasKey('errors', $content);
        self::assertStringContainsString(
            'Max query depth should be 2',
            $content['errors'][0]['message'],
        );
    }

    private function apqCacheHas(string $schemaName, string $hash): bool
    {
        $key = self::APQ_CACHE_PREFIX . ':' . $schemaName . ':' . $hash;

        /** @var CacheFactory $cache */
        $cache = $this->app->make(CacheFactory::class);

        return $cache->store(self::APQ_CACHE_DRIVER)->has($key);
    }
}
