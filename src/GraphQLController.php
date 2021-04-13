<?php

declare(strict_types = 1);
namespace Rebing\GraphQL;

use Exception;
use GraphQL\Error\DebugFlag;
use GraphQL\Error\Error;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Server\Helper;
use GraphQL\Server\OperationParams;
use GraphQL\Server\RequestError;
use Illuminate\Container\Container;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laragraph\Utils\RequestParser;
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
        $schemaName = $schema;

        /** @var RequestParser $parser */
        $parser = $this->app->make(RequestParser::class);
        $operations = $parser->parseRequest($request);

        // If there are multiple route params we can expect that there
        // will be a schema name that has to be built
        $routeParameters = $this->getRouteParameters($request);

        if (count($routeParameters) > 1) {
            $schemaName = implode('/', $routeParameters);
        }

        if (!$schemaName) {
            $schemaName = config('graphql.default_schema');
        }

        $headers = config('graphql.headers', []);
        $jsonOptions = config('graphql.json_encoding_options', 0);

        $isBatch = is_array($operations);

        $supportsBatching = config('graphql.batching.enable', true);

        if ($isBatch && !$supportsBatching) {
            $data = $this->createBatchingNotSupportedResponse($request->input());

            return response()->json($data, 200, $headers, $jsonOptions);
        }

        $data = Helpers::applyEach(
            function (OperationParams $operation) use ($schemaName): array {
                return $this->executeQuery($schemaName, $operation);
            },
            $operations
        );

        return response()->json($data, 200, $headers, $jsonOptions);
    }

    protected function executeQuery(string $schemaName, OperationParams $params): array
    {
        $debug = config('app.debug')
            ? (DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE)
            : DebugFlag::NONE;

        /** @var GraphQL $graphql */
        $graphql = $this->app->make('graphql');

        /** @var Helper $helper */
        $helper = $this->app->make(Helper::class);
        $errors = $helper->validateOperationParams($params);

        if ($errors) {
            $errors = array_map(
                static function (RequestError $err): Error {
                    return Error::createLocatedError($err);
                },
                $errors
            );

            return $graphql
                ->decorateExecutionResult(new ExecutionResult(null, $errors))
                ->toArray($debug);
        }

        try {
            $query = $this->handleAutomaticPersistQueries($schemaName, $params);
        } catch (AutomaticPersistedQueriesError $e) {
            return $graphql
                ->decorateExecutionResult(new ExecutionResult(null, [$e]))
                ->toArray($debug);
        }

        return $graphql->query(
            $query,
            $params->variables,
            [
                'context' => $this->queryContext($query, $params->variables, $schemaName),
                'schema' => $schemaName,
                'operationName' => $params->operation,
            ]
        );
    }

    protected function queryContext(string $query, ?array $variables, string $schemaName)
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
     */
    protected function handleAutomaticPersistQueries(string $schemaName, OperationParams $operation): string
    {
        $query = $operation->query;

        $apqEnabled = config('graphql.apq.enable', false);

        // Even if APQ is disabled, we keep this logic for the negotiation protocol
        $persistedQuery = $operation->extensions['persistedQuery'] ?? null;

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

        $cache = $this->app->make('cache');

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
        $schemaName = $schema;

        $graphqlPath = '/' . config('graphql.prefix');

        if ($schemaName) {
            $graphqlPath .= '/' . $schemaName;
        }

        $view = config('graphql.graphiql.view', 'graphql::graphiql');

        return view($view, [
            'graphql_schema' => 'graphql_schema',
            'graphqlPath' => $graphqlPath,
            'schema' => $schemaName,
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
