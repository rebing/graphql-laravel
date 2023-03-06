<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support\Directives;

use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\Type;

class ExampleDirective extends Directive
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'exampleDirective',
            'description' => 'This is an example directive',
            'locations' => [
                // See DirectiveLocation constants for all available locations
                DirectiveLocation::QUERY,
            ],
            'args' => [
                'first' => [
                    'description' => 'Description of this argument',
                    'type' => Type::string(),
                ],
            ],
        ]);
    }
}
