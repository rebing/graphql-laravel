<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\ExecutionMiddleware;

use Closure;
use GraphQL\Error\Error;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\Parser;

class UnusedVariablesMiddleware extends ExecutionMiddleware
{
    /**
     * @inheritdoc
     */
    public function handle($query, $args, array $opts, Closure $next)
    {
        if (is_string($query)) {
            try {
                $query = Parser::parse($query);
            } catch (Error $error) {
                return new ExecutionResult(null, [$error]);
            }
        }

        $unusedVariables = $args;

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

        return $next($query, $args, $opts);
    }
}
