<?php

declare(strict_types = 1);
namespace Rebing\GraphQL;

use Exception;
use GraphQL\Executor\ExecutionResult;
use Illuminate\Container\Container;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Rebing\GraphQL\Error\AutomaticPersistedQueriesError;

class GraphQLController extends Controller
{
    /** @var Container */
    protected $app;

    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    public function query(Request $request, string $schema = null): JsonResponse
    {
        $middleware = new GraphQLUploadMiddleware();
        $request = $middleware->processRequest($request);

        // If there are multiple route params we can expect that there
        // will be a schema name that has to be built
        $routeParameters = $this->getRouteParameters($request);

        if (count($routeParameters) > 1) {
            $schema = implode('/', $routeParameters);
        }

        if (!$schema) {
            $schema = config('graphql.default_schema');
        }

        $headers = config('graphql.headers', []);
        $jsonOptions = config('graphql.json_encoding_options', 0);

        // check if is batch (check if the array is associative)
        $isBatch = !Arr::isAssoc($request->input());

        $supportsBatching = config('graphql.batching.enable', true);

        if ($isBatch && !$supportsBatching) {
            $data = $this->createBatchingNotSupportedResponse($request->input());

            return response()->json($data, 200, $headers, $jsonOptions);
        }

        $inputs = $isBatch ? $request->input() : [$request->input()];

        $completedQueries = [];

        // Complete each query in order
        foreach ($inputs as $input) {
            $completedQueries[] = $this->executeQuery($schema, $input ?: []);
        }

        $data = $isBatch ? $completedQueries : $completedQueries[0];

        return response()->json($data, 200, $headers, $jsonOptions);
    }

    protected function executeQuery(string $schema, array $input): array
    {
        /** @var GraphQL $graphql */
        $graphql = $this->app->make('graphql');

        try {
            $query = $this->handleAutomaticPersistQueries($schema, $input);
        } catch (AutomaticPersistedQueriesError $e) {
            return $graphql
                ->decorateExecutionResult(new ExecutionResult(null, [$e]))
                ->toArray();
        }

        return $graphql->query(
            $query,
            $params,
            [
                'context' => $this->queryContext($query, $params, $schema),
                'schema' => $schema,
                'operationName' => $input['operationName'] ?? null,
            ]
        );
    }

    protected function queryContext(string $query, ?array $params, string $schema)
    {
        try {
            return $this->app->make('auth')->user();
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Note: it's expected this is called even when APQ is disabled to adhere
     *       to the negotiation protocol.
     *
     * @param array<string,mixed> $input
     */
    protected function handleAutomaticPersistQueries(string $schemaName, array $input): string
    {
        $query = $input['query'] ?? '';
        $apqEnabled = config('graphql.apq.enable', false);

        // Even if APQ is disabled, we keep this logic for the negotiation protocol
        $persistedQuery = $input['extensions']['persistedQuery'] ?? null;

        if ($persistedQuery && !$apqEnabled) {
            throw AutomaticPersistedQueriesError::persistedQueriesNotSupported();
        }

        // APQ disabled? Nothing to be done
        if (!$apqEnabled) {
            return $query;
        }

        // No hash? Nothing to be done
        $hash = $persistedQuery['sha256Hash'] ?? null;

        if (null === $hash) {
            return $query;
        }

        $apqCacheDriver = config('graphql.apq.cache_driver');
        $apqCachePrefix = config('graphql.apq.cache_prefix');
        $apqCacheIdentifier = "$apqCachePrefix:$schemaName:$hash";

        $cache = cache();

        // store in cache
        if ($query) {
            if ($hash !== hash('sha256', $query)) {
                throw AutomaticPersistedQueriesError::invalidHash();
            }
            $ttl = config('graphql.apq.cache_ttl', 300);
            $cache->driver($apqCacheDriver)->set($apqCacheIdentifier, $query, $ttl);

            return $query;
        }

        // retrieve from cache
        if (!$cache->has($apqCacheIdentifier)) {
            throw AutomaticPersistedQueriesError::persistedQueriesNotFound();
        }

        return $cache->driver($apqCacheDriver)->get($apqCacheIdentifier);
    }

    public function graphiql(Request $request, string $schema = null): View
    {
        $graphqlPath = '/' . config('graphql.prefix');

        if ($schema) {
            $graphqlPath .= '/' . $schema;
        }

        $view = config('graphql.graphiql.view', 'graphql::graphiql');

        return view($view, [
            'graphql_schema' => 'graphql_schema',
            'graphqlPath' => $graphqlPath,
            'schema' => $schema,
        ]);
    }

    /**
     * @return array<string,string>
     */
    protected function getRouteParameters(Request $request): array
    {
        if (Helpers::isLumen()) {
            /** @var array<int,mixed> $route */
            $route = $request->route();

            return $route[2] ?? [];
        }

        return $request->route()->parameters;
    }

    /**
     * In case batching is not supported, send an error back for each batch
     * (with a hardcoded limit of 100).
     *
     * The returned format still matches the GraphQL specs
     *
     * @param array<string,mixed> $input
     * @return array<array{errors:array<array{message:string}>}>
     */
    protected function createBatchingNotSupportedResponse(array $input): array
    {
        $count = min(count($input), 100);

        $data = [];

        for ($i = 0; $i < $count; $i++) {
            $data[] = [
                'errors' => [
                    [
                        'message' => 'Batch request received but batching is not supported',
                    ],
                ],
            ];
        }

        return $data;
    }
}
