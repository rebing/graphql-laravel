<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\ExecutionMiddleware;

use Closure;
use GraphQL\Error\Error;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Server\Helper;
use GraphQL\Server\RequestError;
use Rebing\GraphQL\Support\OperationParams;

class ValidateOperationParamsMiddleware extends AbstractExecutionMiddleware
{
    /** @var Helper */
    private $helper;

    public function __construct(Helper $helper)
    {
        $this->helper = $helper;
    }
    /**
     * @inheritdoc
     */
    public function handle(string $schemaName, OperationParams $params, $rootValue, $contextValue, Closure $next)
    {
        $errors = $this->helper->validateOperationParams($params);

        if ($errors) {
            $errors = array_map(
                static function (RequestError $err): Error {
                    return Error::createLocatedError($err);
                },
                $errors
            );

            return new ExecutionResult(null, $errors);
        }

        return $next($schemaName, $params, $rootValue, $contextValue);
    }
}
