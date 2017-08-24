<?php

namespace Rebing\GraphQL\Fields;

use Carbon\Carbon;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Field;

class DateField extends Field
{
    protected $field = null;
    protected $attributes = [
        'description' => 'A date field',
    ];

    public function __construct($field = null, $description = null)
    {
        $this->field = $field;
        $this->attributes['description'] = $description ?? $this->attributes['description'];
    }

    public function type()
    {
        return Type::string();
    }

    public function args()
    {
        return [
            'format' => [
                'type' => Type::string(),
                'description' => 'The [date_format()](http://php.net/manual/function.date.php) function returns a date formatted according to the specified format. **Default:** `Y-m-d H:i:s` in ' . config('app.timezone'),
                'defaultValue' => 'Y-m-d H:i:s',
            ],
        ];
    }

    public function resolve($root, $args)
    {
        if (!$this->field) {
            return null;
        }

        $field = $this->field;
        $date = $root->$field;

        if ($date instanceof Carbon) {
            return $date->format($args['format']);
        }

        return $date;
    }

}
