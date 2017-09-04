<?php
namespace Brevis\Model\mysql;

use xPDO\xPDO;

class FeeType extends \Brevis\Model\FeeType
{

    public static $metaMap = array (
        'package' => 'Brevis\\Model',
        'version' => '3.0',
        'table' => 'fee_types',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'fields' => 
        array (
            'name' => NULL,
            'active' => 0,
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
            'active' => 
            array (
                'dbtype' => 'tinyint',
                'phptype' => 'int',
                'precision' => '1',
                'null' => false,
                'default' => 0,
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
        ),
        'composites' => 
        array (
            'Fees' => 
            array (
                'class' => '\\Brevis\\Model\\Fee',
                'local' => 'id',
                'foreign' => 'type_id',
                'cardinality' => 'many',
                'owner' => 'local',
            ),
        ),
    );
}
