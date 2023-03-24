<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

abstract class AbstractPaginationType extends ObjectType
{
    private Type $underlyingType;

    public function __construct(string $typeName, string $customName = null)
    {
        $name = $this->paginationTypeName($typeName, $customName);

        $this->underlyingType = GraphQL::type($typeName);

        $config = [
            'name' => $name,
            'fields' => $this->getPaginationFields(),
        ];

        if (isset($this->underlyingType->config['model'])) {
            $config['model'] = $this->underlyingType->config['model'];
        }

        parent::__construct($config);
    }

    protected function underlyingType(): Type
    {
        return $this->underlyingType;
    }

    abstract protected function paginationTypeName(string $typeName, string $customName = null): string;

    /**
     * @return array<string, mixed>
     */
    abstract public function getPaginationFields(): array;
}
