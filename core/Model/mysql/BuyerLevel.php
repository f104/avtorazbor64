<?php
namespace Brevis\Model\mysql;

use xPDO\xPDO;

class BuyerLevel extends \Brevis\Model\BuyerLevel
{

    public static $metaMap = array (
        'package' => 'Brevis\\Model',
        'version' => '3.0',
        'table' => 'buyer_levels',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'fields' => 
        array (
            'name' => NULL,
            'increase' => 10,
            'allow_remove' => 1,
        ),
        'fieldMeta' => 
        array (
            'name' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '50',
                'null' => false,
            ),
            'increase' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '2',
                'null' => false,
                'default' => 10,
            ),
            'allow_remove' => 
            array (
                'dbtype' => 'tinyint',
                'phptype' => 'integer',
                'precision' => '1',
                'attributes' => 'unsigned',
                'null' => false,
                'default' => 1,
            ),
        ),
        'indexes' => 
        array (
            'name' => 
            array (
                'alias' => 'name',
                'primary' => false,
                'unique' => true,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'name' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
            'increase' => 
            array (
                'alias' => 'increase',
                'primary' => false,
                'unique' => true,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'increase' => 
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
            'Users' => 
            array (
                'class' => '\\Brevis\\Model\\User',
                'local' => 'id',
                'foreign' => 'buyer_level',
                'cardinality' => 'many',
            ),
        ),
    );
}
