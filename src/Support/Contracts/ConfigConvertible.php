<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\Contracts;

use Rebing\GraphQL\Support\ExecutionMiddleware\AbstractExecutionMiddleware;

interface ConfigConvertible
{
    /**
     * @return array{
     *                query:array<string,class-string>|array<class-string>,
     *                mutation?:array<string,class-string>|array<class-string>,
     *                types?:array<string,class-string>|array<class-string>,
     *                middleware?:array<string|class-string>,
     *                execution_middleware?:array<class-string<AbstractExecutionMiddleware>>
     *                }
     */
    public function toConfig(): array;
}
