<?php
namespace Brevis\Model\mysql;

use xPDO\xPDO;

class Increase extends \Brevis\Model\Increase
{

    public static $metaMap = array (
        'package' => 'Brevis\\Model',
        'version' => '3.0',
        'table' => 'increase_categories',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'fields' => 
        array (
            'increase' => NULL,
            'allow_remove' => 1,
        ),
        'fieldMeta' => 
        array (
            'increase' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'attributes' => 'unsigned',
                'null' => false,
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
            'Element' => 
            array (
                'class' => '\\Brevis\\Model\\Element',
                'local' => 'id',
                'foreign' => 'increase_category_id',
                'cardinality' => 'many',
            ),
        ),
    );
}
