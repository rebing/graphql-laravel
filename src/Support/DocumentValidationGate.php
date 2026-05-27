<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support;

use GraphQL\Error\Error;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Type\Schema;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\QueryComplexity;

/**
 * Validates a parsed GraphQL document against the library's configured
 * validation rules and returns any errors.
 *
 * Mirrors the prime + validate sequence inside webonyx's
 * `GraphQL::promiseToExecute()` so that the same validation produces the
 * same errors regardless of which execution middleware invokes it. With
 * this gate in place, `GraphqlExecutionMiddleware` passes
 * `$validationRules = []` to `GraphQLBase::executeQuery()` to skip
 * webonyx's internal validation pass — there is exactly one place in the
 * pipeline where document validation runs.
 *
 * @internal Implementation detail of the execution middleware pipeline.
 */
class DocumentValidationGate
{
    /**
     * @param array<string,mixed>|null $variables
     * @return list<Error>
     */
    public static function validate(Schema $schema, DocumentNode $document, ?array $variables): array
    {
        /** @var QueryComplexity $queryComplexity */
        $queryComplexity = DocumentValidator::getRule(QueryComplexity::class);
        $queryComplexity->setRawVariableValues($variables);

        return DocumentValidator::validate($schema, $document);
    }
}
