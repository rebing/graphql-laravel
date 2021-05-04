<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\ExecutionMiddleware;

use Closure;
use GraphQL\Error\Error;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Language\AST\OperationDefinitionNode;
use Rebing\GraphQL\Support\OperationParams;

class UnusedVariablesMiddleware extends AbstractExecutionMiddleware
{
    /**
     * @inheritdoc
     */
    public function handle(string $schemaName, OperationParams $params, $rootValue, $contextValue, Closure $next)
    {
        $unusedVariables = $params->variables;

        if (!$unusedVariables) {
            return $next($schemaName, $params, $rootValue, $contextValue);
        }

        $query = $params->getParsedQuery();

        foreach ($query->definitions as $definition) {
            if ($definition instanceof OperationDefinitionNode) {
                foreach ($definition->variableDefinitions as $variableDefinition) {
                    unset($unusedVariables[$variableDefinition->variable->name->value]);
                }
            }
        }

        if ($unusedVariables) {
            $msg = sprintf(
                'The following variables were provided but not consumed: %s',
                implode(', ', array_keys($unusedVariables))
            );

            return new ExecutionResult(null, [new Error($msg)]);
        }

        return $next($schemaName, $params, $rootValue, $contextValue);
    }
}
