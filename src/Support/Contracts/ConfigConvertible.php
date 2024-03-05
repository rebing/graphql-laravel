<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\Contracts;

use Rebing\GraphQL\Support\ExecutionMiddleware\AbstractExecutionMiddleware;

interface ConfigConvertible
{
    /**
     * @return array{
     *                execution_middleware?:list<class-string<AbstractExecutionMiddleware>>,
     *                method?:string|string[],
     *                middleware?:array<string|class-string>,
     *                mutation?:array<string,class-string>|list<class-string>,
     *                query:array<string,class-string>|list<class-string>,
     *                types?:array<string,class-string>|list<class-string>
     *                }
     */
    public function toConfig(): array;
}
