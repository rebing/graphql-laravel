<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Type\Location;

use Models\Location;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class LocationType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Location',
        'description' => 'A location on the map',
        'model' => Location::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Id of the location',
            ],
            'country_code' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Country code of the location (e.g "EE")',
            ],
            'address' => [
                'type' => Type::string(),
                'description' => 'Location\'s address (street, house nr, etc)',
            ],
            'city' => [
                'type' => Type::string(),
                'description' => 'Location\'s city',
            ],
            'post_code' => [
                'type' => Type::int(),
                'description' => 'Post code of the location',
            ],
            'latitude' => [
                'type' => Type::float(),
                'description' => 'Latitude of the location',
            ],
            'longitude' => [
                'type' => Type::float(),
                'description' => 'Longitude of the location',
            ],
        ];
    }
}
