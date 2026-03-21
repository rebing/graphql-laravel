<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\Facades;

use GraphQL\Error\Error;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\NullableType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use Illuminate\Support\Facades\Facade;
use Rebing\GraphQL\GraphQL as RealGraphQL;
use Rebing\GraphQL\Support\OperationParams;

/**
 * @method static Schema schema(?string $schemaName = null)
 * @method static array<string, mixed> query(string $query, ?array<string, mixed> $variables = null, array<string, mixed> $opts = [])
 * @method static ExecutionResult queryAndReturnResult(string $query, ?array<string, mixed> $variables = null, array<string, mixed> $opts = [])
 * @method static array<string, mixed> execute(string $schemaName, OperationParams $operationParams, mixed $rootValue = null, mixed $contextValue = null)
 * @method static void appendGlobalResolverMiddleware(object|string $class)
 * @method static void prependGlobalResolverMiddleware(object|string $class)
 * @method static list<\Rebing\GraphQL\Support\Middleware|class-string<\Rebing\GraphQL\Support\Middleware>> getPrependedGlobalResolverMiddlewares()
 * @method static list<\Rebing\GraphQL\Support\Middleware|class-string<\Rebing\GraphQL\Support\Middleware>> getGlobalResolverMiddlewares()
 * @method static void addTypes(array<int|string, Type|class-string<Type>>|string $types)
 * @method static void addType(Type|class-string<Type>|string $class, ?string $name = null)
 * @method static (NullableType&Type)|NonNull type(string $name, bool $fresh = false)
 * @method static Type getType(string $name, bool $fresh = false)
 * @method static InterfaceType interfaceType(string $name, bool $fresh = false)
 * @method static Type objectType(ObjectType|array<int|string,class-string<\Rebing\GraphQL\Support\Field>|\Rebing\GraphQL\Support\Field|array<string, mixed>>|class-string<\Rebing\GraphQL\Support\Contracts\TypeConvertible>|\Rebing\GraphQL\Support\Contracts\TypeConvertible $type, array{name?: string|null, description?: string|null} $opts = [])
 * @method static void addSchema(string $name, Schema $schema)
 * @method static Schema buildSchemaFromConfig(array<string, mixed> $schemaConfig)
 * @method static void clearType(string $name)
 * @method static void clearSchema(string $name)
 * @method static void clearTypes()
 * @method static void clearSchemas()
 * @method static array<string, object|string> getTypes()
 * @method static array<string, Schema> getSchemas()
 * @method static Type paginate(string $typeName, ?string $customName = null)
 * @method static Type simplePaginate(string $typeName, ?string $customName = null)
 * @method static Type cursorPaginate(string $typeName, ?string $customName = null)
 * @method static Type wrapType(string $typeName, string $customTypeName, class-string<Type> $wrapperTypeClass)
 * @method static array<string, mixed> formatError(Error $e)
 * @method static Error[] handleErrors(Error[] $errors, callable $formatter)
 * @method static array<string, array<string, mixed>> getNormalizedSchemasConfiguration()
 * @method static array<string, mixed> getNormalizedSchemaConfiguration(string $schemaName)
 * @method static ExecutionResult decorateExecutionResult(ExecutionResult $executionResult)
 * @method static \Illuminate\Contracts\Config\Repository getConfigRepository()
 * @see RealGraphQL
 */
class GraphQL extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return class-string<RealGraphQL>
     */
    protected static function getFacadeAccessor(): string
    {
        return RealGraphQL::class;
    }
}
