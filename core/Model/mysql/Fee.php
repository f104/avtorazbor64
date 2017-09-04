<?php
namespace Brevis\Model\mysql;

use xPDO\xPDO;

class Fee extends \Brevis\Model\Fee
{

    public static $metaMap = array (
        'package' => 'Brevis\\Model',
        'version' => '3.0',
        'table' => 'fees',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'fields' => 
        array (
            'user_id' => NULL,
            'timestamp' => 'CURRENT_TIMESTAMP',
            'type_id' => NULL,
            'sum' => 0,
            'comment' => NULL,
            'order_id' => NULL,
            'inv_id' => NULL,
        ),
        'fieldMeta' => 
        array (
            'user_id' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'attributes' => 'unsigned',
            ),
            'timestamp' => 
            array (
                'dbtype' => 'timestamp',
                'phptype' => 'timestamp',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ),
            'type_id' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'attributes' => 'unsigned',
            ),
            'sum' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'default' => 0,
            ),
            'comment' => 
            array (
                'dbtype' => 'varshar',
                'phptype' => 'string',
                'precision' => '500',
                'null' => true,
            ),
            'order_id' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => true,
                'attributes' => 'unsigned',
            ),
            'inv_id' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => true,
                'attributes' => 'unsigned',
            ),
        ),
        'aggregates' => 
        array (
            'User' => 
            array (
                'class' => '\\Brevis\\Model\\User',
                'local' => 'user_id',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
            'FeeType' => 
            array (
                'class' => '\\Brevis\\Model\\FeeType',
                'local' => 'type_id',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
            'Order' => 
            array (
                'class' => '\\Brevis\\Model\\Order',
                'local' => 'order_id',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
        ),
    );
}
