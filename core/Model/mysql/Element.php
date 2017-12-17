<?php
namespace Brevis\Model\mysql;

use xPDO\xPDO;

class Element extends \Brevis\Model\Element
{

    public static $metaMap = array (
        'package' => 'Brevis\\Model',
        'version' => '3.0',
        'table' => 'element',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'fields' => 
        array (
            'category_key' => '',
            'key' => '',
            'name' => '',
            'increase_category' => 1,
            'increase_category_id' => 1,
        ),
        'fieldMeta' => 
        array (
            'category_key' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '1',
                'null' => false,
                'default' => '',
            ),
            'key' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '4',
                'null' => false,
                'default' => '',
            ),
            'name' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '500',
                'null' => false,
                'default' => '',
            ),
            'increase_category' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'default' => 1,
            ),
            'increase_category_id' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'default' => 1,
            ),
        ),
        'indexes' => 
        array (
            'category_key' => 
            array (
                'alias' => 'category_key',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'category_key' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
            'key' => 
            array (
                'alias' => 'key',
                'primary' => false,
                'unique' => true,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'key' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
        ),
        'aggregates' => 
        array (
            'Increas' => 
            array (
                'class' => '\\Brevis\\Model\\Increase',
                'local' => 'increase_category_id',
                'foreign' => 'id',
                'cardinality' => 'one',
            ),
            'Category' => 
            array (
                'class' => '\\Brevis\\Model\\Category',
                'local' => 'category_key',
                'foreign' => 'key',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
        ),
    );
}
