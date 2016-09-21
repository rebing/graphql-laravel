<?php

namespace Rebing\GraphQL\Commands;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ObjectType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateDocumentation extends Command {

    protected $signature = 'graphql:generate-doc';

    protected $description = 'Creates a GraphQL documentation file in the root of your project';

    private $path = 'GraphQL-doc.md';

    public function handle()
    {
        $this->createFile();

        $this->writeTypes();
        $this->writeQueriesOrMutations(true);
        $this->writeQueriesOrMutations(false);
    }

    protected function createFile()
    {
        $title = '#This project\'s Type, Query and Mutation documentation
        
* [Types](#types)
* [Queries](#queries)
* [Mutations](#mutations)
        
';
        File::put($this->path, $title);
    }

    protected function writeTypes()
    {
        $text = '#Types
        
';

        // Block for each type
        $typeNames = '';
        $subtext = '';
        foreach(config('graphql.types') as $typeName => $className)
        {
            $typeNames .= '* [' . $typeName . '](#' . $typeName . '-type)
';
            $class = app($className);

            $attributes = $class->getAttributes();
            $subtext = $this->addTitle($subtext, $typeName, $attributes, 'type');

            $this->addFields($subtext, $class->fields());

            $subtext .= '
';
        }

        $text .= $typeNames . '
';
        $text .= $subtext . '
        
';

        File::append($this->path, $text);
    }

    protected function writeQueriesOrMutations($query = true)
    {
        $text = '#' . ($query ? 'Queries' : 'Mutations') . '
';

        // Block for each query or mutation
        $queryNames = '';
        $subtext = '';
        $type = $query ? 'query' : 'mutation';
        foreach(config('graphql.schema.' . $type) as $queryName => $className)
        {
            $queryNames .= '* [' . $queryName . '](#' . $queryName . '-' . $type . ')
';
            $class = app($className);

            $attributes = $class->getAttributes();
            $subtext = $this->addTitle($subtext, $queryName, $attributes, $type);

            $this->addArgs($subtext, $class->args());

            $this->addReturnType($subtext, $class->type());

            $subtext .= '
';
        }

        $text .= $queryNames . '
';
        $text .= $subtext . '
        
';

        File::append($this->path, $text);
    }

    private function addFields(&$subtext, array $fields)
    {
        foreach($fields as $name => $field)
        {
            $isCustomField = is_a($field['type'], ObjectType::class);
            $typeString = $isCustomField
                ? '([' . $field['type'] . '](#' . strtolower(str_replace(' ', '_', $field['type'])) . '-type))'
                : '(' . $field['type'] . ')';

            $subtext .= '- **' . $name . '** ' . $typeString;
            if(isset($field['description'])) $subtext .= ': ' . $field['description'];
            $subtext .= '
';
        }
    }

    private function addArgs(&$subtext, array $arguments)
    {
        foreach($arguments as $arg)
        {
            $subtext .= '- ' . $arg['name'] . ': ' . $arg['type'];
            if(isset($arg['rules'])) $subtext .= ' **(' . implode(', ', $arg['rules']) . ')**';
            $subtext .= '
';
        }
    }

    private function addTitle(&$subtext, $name, array $attributes, $type)
    {
        $subtext .= '### <a name="' . $name . '-' . $type . '"></a>' . $name . '
';
        if(isset($attributes['description'])) $subtext .= $attributes['description'] . '
';

        return $subtext;
    }

    private function addReturnType(&$text, $type)
    {
        if(is_a($type, ListOfType::class))
        {
            $type = $type->getWrappedType();
        }

        $text .= '
Returns [' . $type->config['name'] . '](#' . str_replace(' ', '_', (strtolower($type->config['name']))) . '-type)
';
    }

}