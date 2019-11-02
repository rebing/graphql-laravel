<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Support\Facades;

use GraphQL\Error\Error;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array query(string $query, ?array $params = [], array $opts = [])
 * @method static ExecutionResult queryAndReturnResult(string $query, ?array $params = [], array $opts = [])
 * @method static Type type(string $name, bool $fresh = false)
 * @method static Type paginate(string $typeName, string $customName = null)
 * @method static array<string,object|string> getTypes()
 * @method static Schema schema(Schema|array|string $schema = null)
 * @method static array getSchemas()
 * @method static void addSchema(string $name, Schema|array $schema)
 * @method static void addType(object|string $class, string $name = null)
 * @method static Type objectType(ObjectType|array|string $type, array $opts = [])
 * @method static array formatError(Error $e)
 * @method static Type wrapType(string $typeName, string $customTypeName, string $wrapperTypeClass)
 */
class GraphQL extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'graphql';
    }
}
