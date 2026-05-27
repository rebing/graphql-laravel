<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\ExecutionMiddleware;

use Closure;
use GraphQL\Executor\ExecutionResult;
use GraphQL\GraphQL as GraphQLBase;
use GraphQL\Type\Schema;
use Illuminate\Contracts\Config\Repository;
use Rebing\GraphQL\Support\DocumentValidationGate;
use Rebing\GraphQL\Support\OperationParams;

/**
 * This middleware is always added via \Rebing\GraphQL\GraphQL::appendGraphqlExecutionMiddleware
 */
class GraphqlExecutionMiddleware extends AbstractExecutionMiddleware
{
    /** @var Repository */
    private $config;

    public function __construct(Repository $config)
    {
        $this->config = $config;
    }

    public function handle(string $schemaName, Schema $schema, OperationParams $params, $rootValue, $contextValue, Closure $next): ExecutionResult
    {
        $document = $params->getParsedQuery();

        // Validation runs once, here. webonyx is told to skip its internal pass
        // by passing `[]` as the validationRules argument below; see
        // `DocumentValidationGate` for the rationale.
        $validationErrors = DocumentValidationGate::validate(
            $schema,
            $document,
            $params->variables,
        );

        if ([] !== $validationErrors) {
            return new ExecutionResult(null, $validationErrors);
        }

        $defaultFieldResolver = $this->config->get('graphql.defaultFieldResolver');

        return GraphQLBase::executeQuery(
            $schema,
            $document,
            $rootValue,
            $contextValue,
            $params->variables,
            $params->operation,
            $defaultFieldResolver,
            [], // See above: passing [] skips webonyx's internal validation
        );
    }
}
