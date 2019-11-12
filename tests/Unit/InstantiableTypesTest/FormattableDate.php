<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\InstantiableTypesTest;

use Carbon\Carbon;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Field;

class FormattableDate extends Field
{
    protected $attributes = [
        'description' => 'A field that can format dates in all sorts of ways.',
    ];

    protected $defaultFormat;

    public function __construct(array $settings = [], string $defaultFormat = 'Y-m-d H:i')
    {
        $this->attributes = \array_merge($this->attributes, $settings);

        $this->defaultFormat = $defaultFormat;
    }

    public function args(): array
    {
        return [
            'format' => [
                'type' => Type::string(),
                'defaultValue' => $this->defaultFormat,
                'description' => \sprintf('Defaults to %s', $this->defaultFormat),
            ],
            'relative' => [
                'type' => Type::boolean(),
                'defaultValue' => false,
            ],
        ];
    }

    public function type(): Type
    {
        return Type::string();
    }

    public function resolve($root, array $args): ?string
    {
        $date = $root->{$this->getProperty()};

        if (!$date instanceof Carbon) {
            return null;
        }

        if ($args['relative']) {
            return $date->diffForHumans();
        }
        
        return $date->format($args['format']);
    }

    protected function getProperty(): string
    {
        return $this->attributes['alias'] ?? $this->attributes['name'];
    }
}
