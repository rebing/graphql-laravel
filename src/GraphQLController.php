<?php

declare(strict_types=1);

namespace Rebing\GraphQL;

use Closure;
use Exception;
use GraphQL\Server\OperationParams;
use Illuminate\Container\Container;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laragraph\LaravelGraphQLUtils\RequestParser;

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
        /** @var RequestParser $parser */
        $parser = $this->app->make(RequestParser::class);
        $operations = $parser->parseRequest($request);

        // If there are multiple route params we can expect that there
        // will be a schema name that has to be built
        $routeParameters = $this->getRouteParameters($request);
        if (count($routeParameters) > 1) {
            $schema = implode('/', $routeParameters);
        }

        if (! $schema) {
            $schema = config('graphql.default_schema');
        }

        $data = static::applyEach(
            function (OperationParams $operation) use ($schema): array {
                return $this->executeOperation($schema, $operation);
            },
            $operations
        );

        $headers = config('graphql.headers', []);
        $jsonOptions = config('graphql.json_encoding_options', 0);

        return response()->json($data, 200, $headers, $jsonOptions);
    }

    protected function executeOperation(string $schema, OperationParams $operation): array
    {
        $query = $operation->query;
        $params = $operation->variables;

        return $this->app->make('graphql')->query(
            $query,
            $params,
            [
                'context' => $this->queryContext($query, $params, $schema),
                'schema' => $schema,
                'operationName' => $operation->operation,
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

    /**
     * Originally from \Nuwave\Lighthouse\Support\Utils::applyEach.
     *
     * Apply a callback to a value or each value in an array.
     *
     * @param  mixed|array<mixed>  $valueOrValues
     * @return mixed|array<mixed>
     */
    public static function applyEach(Closure $callback, $valueOrValues)
    {
        if (! is_array($valueOrValues)) {
            return $callback($valueOrValues);
        }

        return array_map($callback, $valueOrValues);
    }
}
