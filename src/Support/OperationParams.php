<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support;

use GraphQL\Error\Error;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\Parser;
use GraphQL\Server\OperationParams as BaseOperationParams;

class OperationParams extends BaseOperationParams
{
    /** @var DocumentNode|null */
    private $parsedQuery;

    /** @var BaseOperationParams */
    private $baseOperationParams;

    public function __construct(BaseOperationParams $baseOperationParams)
    {
        $this->baseOperationParams = $baseOperationParams;
    }

    public static function fromBaseOperationParams(BaseOperationParams $baseOperationParams): OperationParams
    {
        $operationParams = new static($baseOperationParams);
        $operationParams->queryId = $baseOperationParams->queryId;
        $operationParams->query = $baseOperationParams->query;
        $operationParams->operation = $baseOperationParams->operation;
        $operationParams->variables = $baseOperationParams->variables;
        $operationParams->extensions = $baseOperationParams->extensions;

        return $operationParams;
    }

    public function getOriginalInput($key)
    {
        return $this->baseOperationParams->getOriginalInput($key);
    }

    public function isReadOnly()
    {
        return $this->baseOperationParams->isReadOnly();
    }

    public function getParsedQuery(): DocumentNode
    {
        if (!$this->parsedQuery) {
            if (!$this->query) {
                throw new Error('No GraphQL query available');
            }

            $this->parsedQuery = Parser::parse($this->query);
        }

        return $this->parsedQuery;
    }

    /**
     * @return static
     */
    public function setParsedQuery(DocumentNode $parsedQuery)
    {
        $this->parsedQuery = $parsedQuery;

        return $this;
    }
}
