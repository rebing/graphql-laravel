<?php

declare(strict_types=1);

namespace Rebing\GraphQL;

use Error as PhpError;
use Exception;
use GraphQL\Error\Debug;
use GraphQL\Error\Error;
use GraphQL\Error\FormattedError;
use GraphQL\Executor\ExecutionResult;
use GraphQL\GraphQL as GraphQLBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Traits\Macroable;
use Rebing\GraphQL\Error\AuthorizationError;
use Rebing\GraphQL\Error\ValidationError;
use Rebing\GraphQL\Exception\SchemaNotFound;
use Rebing\GraphQL\Exception\TypeNotFound;
use Rebing\GraphQL\Support\Contracts\TypeConvertible;
use Rebing\GraphQL\Support\PaginationType;

class GraphQL
{
    use Macroable;

    /** @var Container */
    protected $app;

    /** @var array<array|Schema> */
    protected $schemas = [];

    /**
     * Maps GraphQL type names to their class name.
     *
     * @var array<string,object|string>
     */
    protected $types = [];

    /** @var Type[] */
    protected $typesInstances = [];

    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    /**
     * @param  Schema|array|string|null  $schema
     * @return Schema
     */
    public function schema($schema = null): Schema
    {
        if ($schema instanceof Schema) {
            return $schema;
        }

        $this->clearTypeInstances();

        $schema = $this->getSchemaConfiguration($schema);

        if ($schema instanceof Schema) {
            return $schema;
        }

        $schemaQuery = $schema['query'] ?? [];
        $schemaMutation = $schema['mutation'] ?? [];
        $schemaSubscription = $schema['subscription'] ?? [];

        $query = $this->objectType($schemaQuery, [
            'name' => 'Query',
        ]);

        $mutation = $this->objectType($schemaMutation, [
            'name' => 'Mutation',
        ]);

        $subscription = $this->objectType($schemaSubscription, [
            'name' => 'Subscription',
        ]);

        return new Schema([
            'query' => $query,
            'mutation' => ! empty($schemaMutation) ? $mutation : null,
            'subscription' => ! empty($schemaSubscription) ? $subscription : null,
            'types' => function () use ($schema) {
                $types = [];
                $schemaTypes = $schema['types'] ?? [];

                if ($schemaTypes) {
                    foreach ($schemaTypes as $name => $type) {
                        $opts = is_numeric($name) ? [] : ['name' => $name];
                        $objectType = $this->objectType($type, $opts);
                        $this->typesInstances[$name] = $objectType;
                        $types[] = $objectType;
                    }
                } else {
                    foreach ($this->getTypes() as $name => $type) {
                        $types[] = $this->type($name);
                    }
                }

                return $types;
            },
            'typeLoader' => config('graphql.lazyload_types', false)
                ? function ($name) {
                    return $this->type($name);
                }
                : null,
        ]);
    }

    /**
     * @param string $query
     * @param array|null  $params
     * @param array  $opts   Additional options, like 'schema', 'context' or 'operationName'
     *
     * @return array
     */
    public function query(string $query, ?array $params = [], array $opts = []): array
    {
        return $this->queryAndReturnResult($query, $params, $opts)->toArray();
    }

    /**
     * @param  string  $query
     * @param  array|null  $params
     * @param  array  $opts  Additional options, like 'schema', 'context' or 'operationName'
     * @return ExecutionResult
     */
    public function queryAndReturnResult(string $query, ?array $params = [], array $opts = []): ExecutionResult
    {
        $context = $opts['context'] ?? null;
        $schemaName = $opts['schema'] ?? null;
        $operationName = $opts['operationName'] ?? null;
        $rootValue = $opts['rootValue'] ?? null;

        $schema = $this->schema($schemaName);

        $errorFormatter = config('graphql.error_formatter', [static::class, 'formatError']);
        $errorsHandler = config('graphql.errors_handler', [static::class, 'handleErrors']);
        $defaultFieldResolver = config('graphql.defaultFieldResolver', null);

        $result = GraphQLBase::executeQuery($schema, $query, $rootValue, $context, $params, $operationName, $defaultFieldResolver)
            ->setErrorsHandler($errorsHandler)
            ->setErrorFormatter($errorFormatter);

        return $result;
    }

    public function addTypes(array $types): void
    {
        foreach ($types as $name => $type) {
            $this->addType($type, is_numeric($name) ? null : $name);
        }
    }

    /**
     * @param  object|string  $class
     * @param  string|null  $name
     */
    public function addType($class, string $name = null): void
    {
        if (! $name) {
            $type = is_object($class) ? $class : $this->app->make($class);
            $name = $type->name;
        }

        $this->types[$name] = $class;
    }

    public function type(string $name, bool $fresh = false): Type
    {
        $modifiers = [];

        while (true) {
            if (preg_match('/^(.+)!$/', $name, $matches)) {
                $name = $matches[1];
                array_unshift($modifiers, 'nonNull');
            } elseif (preg_match('/^\[(.+)]$/', $name, $matches)) {
                $name = $matches[1];
                array_unshift($modifiers, 'listOf');
            } else {
                break;
            }
        }

        $type = $this->getType($name, $fresh);

        foreach ($modifiers as $modifier) {
            $type = Type::$modifier($type);
        }

        return $type;
    }

    public function getType(string $name, bool $fresh = false): Type
    {
        $standardTypes = Type::getStandardTypes();

        if (in_array($name, $standardTypes)) {
            return $standardTypes[$name];
        }

        if (! isset($this->types[$name])) {
            $error = "Type $name not found.";

            if (config('graphql.lazyload_types', false)) {
                $error .= "\nCheck that the config array key for the type matches the name attribute in the type's class.\nIt is required when 'lazyload_types' is enabled";
            }

            throw new TypeNotFound($error);
        }

        if (! $fresh && isset($this->typesInstances[$name])) {
            return $this->typesInstances[$name];
        }

        $type = $this->types[$name];
        if (! is_object($type)) {
            $type = $this->app->make($type);
        }

        $instance = $type->toType();
        $this->typesInstances[$name] = $instance;

        return $instance;
    }

    /**
     * @param  ObjectType|array|string  $type
     * @param  array<string,string>  $opts
     * @return Type
     */
    public function objectType($type, array $opts = []): Type
    {
        // If it's already an ObjectType, just update properties and return it.
        // If it's an array, assume it's an array of fields and build ObjectType
        // from it. Otherwise, build it from a string or an instance.
        $objectType = null;
        if ($type instanceof ObjectType) {
            $objectType = $type;
            foreach ($opts as $key => $value) {
                if (property_exists($objectType, $key)) {
                    $objectType->{$key} = $value;
                }
                if (isset($objectType->config[$key])) {
                    $objectType->config[$key] = $value;
                }
            }
        } elseif (is_array($type)) {
            $objectType = $this->buildObjectTypeFromFields($type, $opts);
        } else {
            $objectType = $this->buildObjectTypeFromClass($type, $opts);
        }

        return $objectType;
    }

    /**
     * @param  ObjectType|string  $type
     * @param  array  $opts
     * @return Type
     */
    protected function buildObjectTypeFromClass($type, array $opts = []): Type
    {
        if (! is_object($type)) {
            $type = $this->app->make($type);
        }

        if (! $type instanceof TypeConvertible) {
            throw new TypeNotFound(
                sprintf(
                    'Unable to convert %s to a GraphQL type, please add/implement the interface %s',
                    get_class($type),
                    TypeConvertible::class
                )
            );
        }

        foreach ($opts as $key => $value) {
            $type->{$key} = $value;
        }

        return $type->toType();
    }

    protected function buildObjectTypeFromFields(array $fields, array $opts = []): ObjectType
    {
        $typeFields = [];
        foreach ($fields as $name => $field) {
            if (is_string($field)) {
                $field = $this->app->make($field);
                $name = is_numeric($name) ? $field->name : $name;
                $field->name = $name;
                $field = $field->toArray();
            } else {
                $name = is_numeric($name) ? $field['name'] : $name;
                $field['name'] = $name;
            }
            $typeFields[$name] = $field;
        }

        return new ObjectType(array_merge([
            'fields' => $typeFields,
        ], $opts));
    }

    /**
     * @param  string  $name
     * @param  Schema|array  $schema
     */
    public function addSchema(string $name, $schema): void
    {
        $this->mergeSchemas($name, $schema);
    }

    /**
     * @param  string  $name
     * @param  Schema|array  $schema
     */
    public function mergeSchemas(string $name, $schema): void
    {
        if (isset($this->schemas[$name]) && is_array($this->schemas[$name]) && is_array($schema)) {
            $this->schemas[$name] = array_merge_recursive($this->schemas[$name], $schema);
        } else {
            $this->schemas[$name] = $schema;
        }
    }

    public function clearType(string $name): void
    {
        if (isset($this->types[$name])) {
            unset($this->types[$name]);
        }
    }

    public function clearSchema(string $name): void
    {
        if (isset($this->schemas[$name])) {
            unset($this->schemas[$name]);
        }
    }

    public function clearTypes(): void
    {
        $this->types = [];
    }

    public function clearSchemas(): void
    {
        $this->schemas = [];
    }

    /**
     * @return array<string,object|string>
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function getSchemas(): array
    {
        return $this->schemas;
    }

    protected function clearTypeInstances(): void
    {
        $this->typesInstances = [];
    }

    public function paginate(string $typeName, string $customName = null): Type
    {
        $name = $customName ?: $typeName.'Pagination';

        if (! isset($this->typesInstances[$name])) {
            $paginationType = config('graphql.pagination_type', PaginationType::class);
            $this->wrapType($typeName, $name, $paginationType);
        }

        return $this->typesInstances[$name];
    }

    /**
     * To add customs result to the query or mutations.
     *
     * @param string $typeName         The original type name
     * @param string $customTypeName   The new type name
     * @param string $wrapperTypeClass The class to create the new type
     *
     * @return Type
     */
    public function wrapType(string $typeName, string $customTypeName, string $wrapperTypeClass): Type
    {
        if (! isset($this->typesInstances[$customTypeName])) {
            $wrapperClass = new $wrapperTypeClass($typeName, $customTypeName);
            $this->typesInstances[$customTypeName] = $wrapperClass;
            $this->types[$customTypeName] = $wrapperClass;
        }

        return $this->typesInstances[$customTypeName];
    }

    /**
     * @see \GraphQL\Executor\ExecutionResult::setErrorFormatter
     * @param  Error  $e
     * @return array
     */
    public static function formatError(Error $e): array
    {
        $debug = config('app.debug') ? (Debug::INCLUDE_DEBUG_MESSAGE | Debug::INCLUDE_TRACE) : 0;
        $formatter = FormattedError::prepareFormatter(null, $debug);
        $error = $formatter($e);

        $previous = $e->getPrevious();
        if ($previous && $previous instanceof ValidationError) {
            $error['extensions']['validation'] = $previous->getValidatorMessages();
        }

        return $error;
    }

    /**
     * @param  Error[]  $errors
     * @param  callable  $formatter
     * @return Error[]
     */
    public static function handleErrors(array $errors, callable $formatter): array
    {
        $handler = app()->make(ExceptionHandler::class);
        foreach ($errors as $error) {
            // Try to unwrap exception
            $error = $error->getPrevious() ?: $error;

            // Don't report certain GraphQL errors
            if ($error instanceof ValidationError
                || $error instanceof AuthorizationError
                || ! (
                    $error instanceof Exception
                    || $error instanceof PhpError
                )) {
                continue;
            }

            if (! $error instanceof Exception) {
                $error = new \Exception(
                    $error->getMessage(),
                    $error->getCode(),
                    $error
                );
            }

            $handler->report($error);
        }

        return array_map($formatter, $errors);
    }

    /**
     * Check if the schema expects a nest URI name and return the formatted version
     * Eg. 'user/me'
     * will open the query path /graphql/user/me.
     *
     * @param  string  $name
     * @param  string  $schemaParameterPattern
     * @param  string  $queryRoute
     *
     * @return string mixed
     */
    public static function routeNameTransformer(string $name, string $schemaParameterPattern, string $queryRoute): string
    {
        $multiLevelPath = explode('/', $name);
        $routeName = null;

        if (count($multiLevelPath) > 1) {
            if (Helpers::isLumen()) {
                array_walk($multiLevelPath, function (string &$multiName): void {
                    $multiName = "$multiName:$multiName";
                });
            }

            foreach ($multiLevelPath as $multiName) {
                $routeName = ! $routeName ? null : $routeName.'/';
                $routeName =
                    $routeName
                    .preg_replace($schemaParameterPattern, '{'.$multiName.'}', $queryRoute);
            }
        }

        return $routeName ?: preg_replace($schemaParameterPattern, '{'.(Helpers::isLumen() ? "$name:$name" : $name).'}', $queryRoute);
    }

    /**
     * @param  array|string|null  $schema
     * @return array|Schema
     */
    protected function getSchemaConfiguration($schema)
    {
        $schemaName = is_string($schema) ? $schema : config('graphql.default_schema', 'default');

        if (! is_array($schema) && ! isset($this->schemas[$schemaName])) {
            throw new SchemaNotFound('Type '.$schemaName.' not found.');
        }

        return is_array($schema) ? $schema : $this->schemas[$schemaName];
    }
}
