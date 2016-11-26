<?php namespace Rebing\GraphQL;

use GraphQL\Error\Error;
use Rebing\GraphQL\Error\ValidationError;
use GraphQL\GraphQL as GraphQLBase;
use GraphQL\Schema;
use GraphQL\Type\Definition\ObjectType;
use Rebing\GraphQL\Support\PaginationType;
use Session;

class GraphQL {
    
    protected $app;
    
    protected $mutations = [];
    protected $queries = [];
    protected $types = [];
    protected $typesInstances = [];
    
    public function __construct($app)
    {
        $this->app = $app;
    }

    public function schema()
    {
        $this->typesInstances = [];
        
        $schema = config('graphql.schema');
        if($schema instanceof Schema)
        {
            return $schema;
        }
        
        foreach($this->types as $name => $type)
        {
            $this->type($name);
        }
        
        $configQuery = array_get($schema, 'query', []);
        $configMutation = array_get($schema, 'mutation', []);
        
        if(is_string($configQuery))
        {
            $queryType = $this->app->make($configQuery)->toType();
        }
        else
        {
            $queryFields = array_merge($configQuery, $this->queries);
            
            $queryType = $this->buildTypeFromFields($queryFields, [
                'name' => 'Query'
            ]);
        }
        
        if(is_string($configMutation))
        {
            $mutationType = $this->app->make($configMutation)->toType();
        }
        else
        {
            $mutationFields = array_merge($configMutation, $this->mutations);
            
            $mutationType = $this->buildTypeFromFields($mutationFields, [
                'name' => 'Mutation'
            ]);
        }
        
        return new Schema([
            'query'     => $queryType,
            'mutation'  => $mutationType,
        ]);
    }
    
    protected function buildTypeFromFields($fields, $opts = [])
    {
        $typeFields = [];
        foreach($fields as $key => $field)
        {
            if(is_string($field))
            {
                $typeFields[$key] = app($field)->toArray();
            }
            else
            {
                $typeFields[$key] = $field;
            }
        }
        
        return new ObjectType(array_merge([
            'fields' => $typeFields
        ], $opts));
    }

    public function query($query, $params = [])
    {
        $executionResult = $this->queryAndReturnResult($query, $params);

        $data = [
            'data' => $executionResult->data,
        ];

        // Add errors
        if( ! empty($executionResult->errors))
        {
            $errorFormatter = config('graphql.error_formatter', ['\Rebing\GraphQL', 'formatError']);

            $data['errors'] = array_map($errorFormatter, $executionResult->errors);
        }

        return $data;
    }
    
    public function queryAndReturnResult($query, $params = [])
    {
        $schema = $this->schema();
        $result = GraphQLBase::executeAndReturnResult($schema, $query, null, null, $params);
        return $result;
    }
    
    public function addMutation($name, $mutator)
    {
        $this->mutations[$name] = $mutator;
    }
    
    public function addQuery($name, $query)
    {
        $this->queries[$name] = $query;
    }
    
    public function addType($class, $name = null)
    {
        if(!$name)
        {
            $type = is_object($class) ? $class:app($class);
            $name = $type->name;    
        }
        
        $this->types[$name] = $class;
    }
    
    public function type($name, $fresh = false)
    {
        if(!isset($this->types[$name]))
        {
            throw new \Exception('Type '.$name.' not found.');
        }
        
        if(!$fresh && isset($this->typesInstances[$name]))
        {
            return $this->typesInstances[$name];
        }
        
        $type = $this->types[$name];
        if(!is_object($type))
        {
            $type = app($type);
        }
        
        $instance = $type->toType();
        $this->typesInstances[$name] = $instance;
        
        return $instance;
    }

    public function paginate($typeName, $customName = null)
    {
        return new PaginationType($typeName, $customName);
    }
    
    public static function formatError(Error $e)
    {
        $error = [
            'message' => $e->getMessage()
        ];
        
        $locations = $e->getLocations();
        if(!empty($locations))
        {
            $error['locations'] = array_map(function($loc)
            {
                return $loc->toArray();
            }, $locations);
        }
        
        $previous = $e->getPrevious();
        if($previous && $previous instanceof ValidationError)
        {
            $error['validation'] = $previous->getValidatorMessages();
        }
        
        return $error;
    }
}
