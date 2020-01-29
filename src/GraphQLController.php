<?php

declare(strict_types=1);

namespace Rebing\GraphQL;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

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

        if (! $schema) {
            $schema = config('graphql.default_schema');
        }

        // If a singular query was not found, it means the queries are in batch
        $isBatch = ! $request->has('query');
        $inputs = $isBatch ? $request->input() : [$request->input()];

        $completedQueries = [];

        // Complete each query in order
        foreach ($inputs as $input) {
            $completedQueries[] = $this->executeQuery($schema, $input);
        }

        $data = $isBatch ? $completedQueries : $completedQueries[0];

        $headers = config('graphql.headers', []);
        $jsonOptions = config('graphql.json_encoding_options', 0);

        return response()->json($data, 200, $headers, $jsonOptions);
    }

    protected function executeQuery(string $schema, array $input): array
    {
        $query = $input['query'];

        $paramsKey = config('graphql.params_key', 'variables');
        $params = $input[$paramsKey] ?? null;
        if (is_string($params)) {
            $params = json_decode($params, true);
        }

        return $this->app->make('graphql')->query(
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

    public function graphiql(Request $request, string $schema = null): View
    {
        $graphqlPath = '/'.config('graphql.prefix');
        if ($schema) {
            $graphqlPath .= '/'.$schema;
        }

        $view = config('graphql.graphiql.view', 'graphql::graphiql');

        return view($view, [
            'graphql_schema' => 'graphql_schema',
            'graphqlPath' => $graphqlPath,
        ]);
    }

    /**
     * @param  Request  $request
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
}
