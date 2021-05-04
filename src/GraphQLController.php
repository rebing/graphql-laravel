<?php

declare(strict_types = 1);
namespace Rebing\GraphQL;

use Exception;
use GraphQL\Error\DebugFlag;
use GraphQL\Error\Error;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Language\Parser;
use GraphQL\Server\Helper;
use GraphQL\Server\OperationParams;
use GraphQL\Server\RequestError;
use Illuminate\Container\Container;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Laragraph\Utils\RequestParser;
use Rebing\GraphQL\Error\AutomaticPersistedQueriesError;

class GraphQLController extends Controller
{
    public function query(Request $request, RequestParser $parser): JsonResponse
    {
        $routePrefix = config('graphql.route.prefix', 'graphql');
        $schemaName = $this->findSchemaNameInRequest($request, "$routePrefix/") ?? config('graphql.default_schema', 'default');

        $operations = $parser->parseRequest($request);

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
        $graphql = Container::getInstance()->make('graphql');

        /** @var Helper $helper */
        $helper = Container::getInstance()->make(Helper::class);
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
            // In case of an APQ cache hit, parsedQuery contains the AST of the provided query
            // and is subsequently used to speed up the execution.
            [
                'query' => $query,
                'parsedQuery' => $parsedQuery,
            ] = $this->handleAutomaticPersistQueries($schemaName, $params);
        } catch (AutomaticPersistedQueriesError | Error $e) {
            return $graphql
                ->decorateExecutionResult(new ExecutionResult(null, [$e]))
                ->toArray($debug);
        }

        return $graphql->query(
            $params->query,
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
            return Container::getInstance()->make('auth')->user();
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Note: it's expected this is called even when APQ is disabled to adhere
     *       to the negotiation protocol.
     * @return array{query:string,parsedQuery:?\GraphQL\Language\AST\DocumentNode}
     */
    protected function handleAutomaticPersistQueries(string $schemaName, OperationParams $operation): array
    {
        $query = $operation->query;

        $datum = [
            'query' => $query,
            'parsedQuery' => null,
        ];

        $apqEnabled = config('graphql.apq.enable', false);

        // Even if APQ is disabled, we keep this logic for the negotiation protocol
        $persistedQuery = $operation->extensions['persistedQuery'] ?? null;

        if ($persistedQuery && !$apqEnabled) {
            throw AutomaticPersistedQueriesError::persistedQueriesNotSupported();
        }

        // APQ disabled? Nothing to be done
        if (!$apqEnabled) {
            return $datum;
        }

        // No hash? Nothing to be done
        $hash = $persistedQuery['sha256Hash'] ?? null;

        if (null === $hash) {
            return $datum;
        }

        $apqCacheDriver = config('graphql.apq.cache_driver');
        $apqCachePrefix = config('graphql.apq.cache_prefix');
        $apqCacheIdentifier = "$apqCachePrefix:$schemaName:$hash";

        $cache = Container::getInstance()->make('cache');

        // store in cache
        if ($query) {
            if ($hash !== hash('sha256', $query)) {
                throw AutomaticPersistedQueriesError::invalidHash();
            }

            $datum['parsedQuery'] = Parser::parse($query);

            $ttl = config('graphql.apq.cache_ttl', 300);
            $cache->driver($apqCacheDriver)->set($apqCacheIdentifier, $datum, $ttl);

            return $datum;
        }

        // retrieve from cache
        if (!$cache->has($apqCacheIdentifier)) {
            throw AutomaticPersistedQueriesError::persistedQueriesNotFound();
        }

        return $cache->driver($apqCacheDriver)->get($apqCacheIdentifier);
    }

    public function graphiql(Request $request): View
    {
        $routePrefix = config('graphql.graphiql.prefix', 'graphiql');
        $schemaName = $this->findSchemaNameInRequest($request, "$routePrefix/");

        $graphqlPath = '/' . config('graphql.route.prefix', 'graphql');

        if ($schemaName) {
            $graphqlPath .= '/' . $schemaName;
        }

        $view = config('graphql.graphiql.view', 'graphql::graphiql');

        return view($view, [
            'graphqlPath' => $graphqlPath,
            'schema' => $schemaName,
        ]);
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

    protected function findSchemaNameInRequest(Request $request, string $routePrefix): ?string
    {
        $path = $request->path();

        if (!Str::startsWith($path, $routePrefix)) {
            return null;
        }

        return Str::after($path, $routePrefix);
    }
}
