<?php
namespace Brevis\Model\mysql;

use xPDO\xPDO;

class Category extends \Brevis\Model\Category
{

    public static $metaMap = array (
        'package' => 'Brevis\\Model',
        'version' => '3.0',
        'table' => 'category',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'fields' => 
        array (
            'key' => '',
            'name' => '',
        ),
        'fieldMeta' => 
        array (
            'key' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '1',
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
        ),
        'indexes' => 
        array (
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
        'composites' => 
        array (
            'Elements' => 
            array (
                'class' => '\\Brevis\\Model\\Element',
                'local' => 'key',
                'foreign' => 'category_key',
                'cardinality' => 'many',
                'owner' => 'local',
            ),
        ),
    );
}
