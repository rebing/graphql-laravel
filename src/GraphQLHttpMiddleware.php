<?php
declare(strict_types = 1);
namespace Rebing\GraphQL;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GraphQLHttpMiddleware
{
    /**
     * Inject schemaName in server request.
     *
     * @param Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next, string $schemaName): Response
    {
        $request->server->set('graphql.schemaName', $schemaName);

        return $next($request);
    }
}
