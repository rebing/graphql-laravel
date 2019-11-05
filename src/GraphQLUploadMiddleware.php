<?php

declare(strict_types=1);

namespace Rebing\GraphQL;

use Closure;
use GraphQL\Error\InvariantViolation;
use GraphQL\Server\RequestError;
use GraphQL\Utils\Utils;
use Illuminate\Http\Request;

class GraphQLUploadMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $request = $this->processRequest($request);

        return $next($request);
    }

    /**
     * Process the request and return either a modified request or the original one.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Request
     */
    public function processRequest(Request $request): Request
    {
        $contentType = $request->header('content-type') ?: '';

        if (mb_stripos($contentType, 'multipart/form-data') !== false) {
            $this->validateParsedBody($request);
            $request = $this->parseUploadedFiles($request);
        }

        return $request;
    }

    /**
     * Inject uploaded files defined in the 'map' key into the 'variables' key.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Request
     */
    private function parseUploadedFiles(Request $request): Request
    {
        $bodyParams = $request->all();
        if (! isset($bodyParams['map'])) {
            throw new RequestError('The request must define a `map`');
        }

        $map = json_decode($bodyParams['map'], true);
        $result = json_decode($bodyParams['operations'], true);
        if (isset($result['operationName'])) {
            $result['operation'] = $result['operationName'];
            unset($result['operationName']);
        }

        foreach ($map as $fileKey => $locations) {
            foreach ($locations as $location) {
                $items = &$result;
                foreach (explode('.', $location) as $key) {
                    if (! isset($items[$key]) || ! is_array($items[$key])) {
                        $items[$key] = [];
                    }
                    $items = &$items[$key];
                }

                $items = $request->allFiles()[$fileKey];
            }
        }

        $request->replace($result);

        return $request;
    }

    /**
     * Validates that the request meet our expectations.
     *
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    private function validateParsedBody(Request $request): void
    {
        $bodyParams = $request->all();

        if (null === $bodyParams) {
            throw new InvariantViolation(
                'Request is expected to provide parsed body for "multipart/form-data" requests but got null'
            );
        }

        if (! is_array($bodyParams)) {
            throw new RequestError(
                'GraphQL Server expects JSON object or array, but got '.Utils::printSafeJson($bodyParams)
            );
        }

        if (empty($bodyParams)) {
            throw new InvariantViolation(
                'Request is expected to provide parsed body for "multipart/form-data" requests but got empty array'
            );
        }
    }
}
