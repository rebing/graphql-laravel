<?php

declare(strict_types=1);

namespace Rebing\GraphQL;

use Exception;
use RuntimeException;
use GraphQL\Error\Debug;
use GraphQL\Error\Error;
use GraphQL\Type\Schema;
use Illuminate\Support\Arr;
use GraphQL\Error\FormattedError;
use GraphQL\GraphQL as GraphQLBase;
use GraphQL\Type\Definition\ObjectType;
use Rebing\GraphQL\Error\ValidationError;
use Rebing\GraphQL\Support\PaginationType;
use Rebing\GraphQL\Error\AuthorizationError;
use Rebing\GraphQL\Exception\SchemaNotFound;
use Illuminate\Contracts\Debug\ExceptionHandler;

class GraphQL
{
    protected $app;

    protected $schemas = [];
    protected $types = [];
    protected $typesInstances = [];

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function schema($schema = null)
    {
        if ($schema instanceof Schema) {
            return $schema;
        }

        $this->typesInstances = [];

        foreach ($this->getTypes() as $name => $type) {
            $this->type($name);
        }

        $schema = $this->getSchemaConfiguration($schema);

        $schemaQuery = Arr::get($schema, 'query', []);
        $schemaMutation = Arr::get($schema, 'mutation', []);
        $schemaSubscription = Arr::get($schema, 'subscription', []);
        $schemaTypes = Arr::get($schema, 'types', []);

        //Get the types either from the schema, or the global types.
        $types = [];
        if (count($schemaTypes)) {
            foreach ($schemaTypes as $name => $type) {
                $objectType = $this->objectType($type, is_numeric($name) ? [] : [
                    'name' => $name,
                ]);
                $this->typesInstances[$name] = $objectType;
                $types[] = $objectType;
            }
        } else {
            foreach ($this->getTypes() as $name => $type) {
                $types[] = $this->type($name);
            }
        }

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
            'query'         => $query,
            'mutation'      => ! empty($schemaMutation) ? $mutation : null,
            'subscription'  => ! empty($schemaSubscription) ? $subscription : null,
            'types'         => $types,
        ]);
    }

    /**
     * @param string $query
     * @param array  $params
     * @param array  $opts   Additional options, like 'schema', 'context' or 'operationName'
     *
     * @return mixed
     */
    public function query($query, $params = [], $opts = [])
    {
        return $this->queryAndReturnResult($query, $params, $opts)->toArray();
    }

    public function queryAndReturnResult($query, $params = [], $opts = [])
    {
        $context = Arr::get($opts, 'context');
        $schemaName = Arr::get($opts, 'schema');
        $operationName = Arr::get($opts, 'operationName');

        $schema = $this->schema($schemaName);

        $errorFormatter = config('graphql.error_formatter', [static::class, 'formatError']);
        $errorsHandler = config('graphql.errors_handler', [static::class, 'handleErrors']);
        $defaultFieldResolver = config('graphql.defaultFieldResolver', null);

        $result = GraphQLBase::executeQuery($schema, $query, null, $context, $params, $operationName, $defaultFieldResolver)
            ->setErrorsHandler($errorsHandler)
            ->setErrorFormatter($errorFormatter);

        return $result;
    }

    public function addTypes($types)
    {
        foreach ($types as $name => $type) {
            $this->addType($type, is_numeric($name) ? null : $name);
        }
    }

    public function addType($class, $name = null)
    {
        if (! $name) {
            $type = is_object($class) ? $class : app($class);
            $name = $type->name;
        }

        $this->types[$name] = $class;
    }

    public function type($name, $fresh = false)
    {
        if (! isset($this->types[$name])) {
            throw new RuntimeException('Type '.$name.' not found.');
        }

        if (! $fresh && isset($this->typesInstances[$name])) {
            return $this->typesInstances[$name];
        }

        $type = $this->types[$name];
        if (! is_object($type)) {
            $type = app($type);
        }

        $instance = $type->toType();
        $this->typesInstances[$name] = $instance;

        return $instance;
    }

    public function objectType($type, $opts = [])
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

    protected function buildObjectTypeFromClass($type, $opts = [])
    {
        if (! is_object($type)) {
            $type = $this->app->make($type);
        }

        foreach ($opts as $key => $value) {
            $type->{$key} = $value;
        }

        return $type->toType();
    }

    protected function buildObjectTypeFromFields($fields, $opts = [])
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

    public function addSchema($name, $schema)
    {
        $this->mergeSchemas($name, $schema);
    }

    public function mergeSchemas($name, $schema)
    {
        if (isset($this->schemas[$name]) && $this->schemas[$name]) {
            $this->schemas[$name] = array_merge_recursive($this->schemas[$name], $schema);
        } else {
            $this->schemas[$name] = $schema;
        }
    }

    public function clearType($name)
    {
        if (isset($this->types[$name])) {
            unset($this->types[$name]);
        }
    }

    public function clearSchema($name)
    {
        if (isset($this->schemas[$name])) {
            unset($this->schemas[$name]);
        }
    }

    public function clearTypes()
    {
        $this->types = [];
    }

    public function clearSchemas()
    {
        $this->schemas = [];
    }

    public function getTypes()
    {
        return $this->types;
    }

    public function getSchemas()
    {
        return $this->schemas;
    }

    protected function clearTypeInstances()
    {
        $this->typesInstances = [];
    }

    public function paginate($typeName, $customName = null)
    {
        $name = $customName ?: $typeName.'_pagination';

        if (! isset($this->typesInstances[$name])) {
            $paginationType = config('graphql.pagination_type', PaginationType::class);
            $this->typesInstances[$name] = new $paginationType($typeName, $customName);
        }

        return $this->typesInstances[$name];
    }

    public static function formatError(Error $e)
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

    public static function handleErrors(array $errors, callable $formatter)
    {
        $handler = app()->make(ExceptionHandler::class);
        foreach ($errors as $error) {
            // Try to unwrap exception
            $error = $error->getPrevious() ?: $error;
            // Don't report certain GraphQL errors
            if ($error instanceof ValidationError
                || $error instanceof AuthorizationError
                || ! ($error instanceof Exception)) {
                continue;
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
     * @param $name
     * @param $schemaParameterPattern
     * @param $queryRoute
     *
     * @return mixed
     */
    public static function routeNameTransformer($name, $schemaParameterPattern, $queryRoute)
    {
        $multiLevelPath = explode('/', $name);
        $routeName = null;

        if (count($multiLevelPath) > 1) {
            if (Helpers::isLumen()) {
                array_walk($multiLevelPath, function (&$multiName) {
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

    protected function getSchemaConfiguration($schema)
    {
        $schemaName = is_string($schema) ? $schema : config('graphql.default_schema', 'default');

        if (! is_array($schema) && ! isset($this->schemas[$schemaName])) {
            throw new SchemaNotFound('Type '.$schemaName.' not found.');
        }

        return is_array($schema) ? $schema : $this->schemas[$schemaName];
    }
}
