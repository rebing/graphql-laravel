<?php

namespace Rebing\GraphQL\Type\Definition;

use GraphQL\Type\Definition\EnumType;
use Rebing\GraphQL\Support\Type;

class DirectionEnumType extends EnumType
{
    const TYPE_NAME = 'DirectionEnumType';
    const ORDER_BY_ASC = 'ASC';
    const ORDER_BY_DESC = 'DESC';

    public function __construct($config)
    {
        $config = [
            'name' => self::TYPE_NAME,
            'description' => 'Sorting direction',
            'values' => [
                self::ORDER_BY_ASC,
                self::ORDER_BY_DESC
            ]
        ];

        parent::__construct($config);
    }
}
