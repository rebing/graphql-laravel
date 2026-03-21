<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\Facades;

use GraphQL\Error\Error;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\NullableType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use Illuminate\Support\Facades\Facade;
use Rebing\GraphQL\GraphQL as RealGraphQL;
use Rebing\GraphQL\Support\OperationParams;

/**
 * @method static array<string, mixed> execute(string $schemaName, OperationParams $operationParams, $rootValue = null, $contextValue = null)
 * @method static array<string, mixed> query(string $query, ?array<string, mixed> $params = null, array<string, mixed> $opts = [])
 * @method static ExecutionResult queryAndReturnResult(string $query, ?array<string, mixed> $params = null, array<string, mixed> $opts = [])
 * @method static (NullableType&Type)|NonNull type(string $name, bool $fresh = false)
 * @method static Type paginate(string $typeName, string $customName = null)
 * @method static Type simplePaginate(string $typeName, string $customName = null)
 * @method static Type cursorPaginate(string $typeName, string $customName = null)
 * @method static array<string,object|string> getTypes()
 * @method static Schema schema(?string $schema = null)
 * @method static Schema buildSchemaFromConfig(array<string, mixed> $schemaConfig)
 * @method static array<string, mixed> getSchemas()
 * @method static void addSchema(string $name, Schema $schema)
 * @method static list<class-string|object> getGlobalResolverMiddlewares()
 * @method static list<object|class-string> getPrependedGlobalResolverMiddlewares()
 * @method static void appendGlobalResolverMiddleware(object|string $class)
 * @method static void prependGlobalResolverMiddleware(object|string $class)
 * @method static void addType(object|string $class, string $name = null)
 * @method static void addTypes(array<int|string,string> $types)
 * @method static Type objectType(ObjectType|array<string, mixed>|string $type, array<string, mixed> $opts = [])
 * @method static array<string, mixed> formatError(Error $e)
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
        return RealGraphQL::class;
    }
}
