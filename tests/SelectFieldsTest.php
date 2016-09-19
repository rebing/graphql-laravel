<?php

use Rebing\GraphQL\Support\SelectFields;

class SelectFieldsTest extends \PHPUnit_Framework_TestCase {

    public function testSelectableFieldsOnlyTopLevel()
    {
        // Create data with just top level attributes, no relations
        $data = [
            'a' => true,
            'b' => true,
            'c' => true,
        ];

        $fields = SelectFields::getSelectableFieldsAndRelations($data, '');

        // Make sure the selects are correct
        $this->assertEquals(array_keys($data), $fields[0]);
    }

    public function testSelectableFieldsRelationTopLevel()
    {
        // Create data with one level of relation
        $data = [
            'a' => true,
            'b' => [
                'c' => true,
                'd' => true,
            ],
        ];

        $fields = SelectFields::getSelectableFieldsAndRelations($data, '');

        // Make sure the selectable is correct
        $this->assertEquals(['a'], $fields[0]);
        // Make sure the relation is a closure
        $closure = function($query)
        {
            $query->select(['c', 'd']);
            $query->with([]);
        };
        $this->assertEquals(['b' => $closure], $fields[1]);
    }

    public function testSelectableFieldsMultipleLevelRelation()
    {
        // Create a data with a deeper depth
        $data = [
            'a' => true,
            'b' => [
                'c' => true,
                'd' => [
                    'e' => true,
                ],
            ],
            'f' => [
                'g' => true,
            ],
        ];

        $fields = SelectFields::getSelectableFieldsAndRelations($data, '');

        /* ASSERT */
        $this->assertEquals(['a'], $fields[0]);

        $closureA = function($query)
        {
            $query->select(['e']);
            $query->with([]);
        };
        $closureB = function($query) use ($closureA)
        {
            $query->select(['c']);
            $query->with(['d' => $closureA]);
        };
        $closureC = function($query)
        {
            $query->select(['g']);
            $query->with([]);
        };

        $this->assertEquals(['b' => $closureB, 'f' => $closureC], $fields[1]);
    }

}