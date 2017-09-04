<?php
namespace Brevis\Model\mysql;

use xPDO\xPDO;

class OrderStatus extends \Brevis\Model\OrderStatus
{

    public static $metaMap = array (
        'package' => 'Brevis\\Model',
        'version' => '3.0',
        'table' => 'order_statuses',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'fields' => 
        array (
            'name' => NULL,
            'order' => 0,
            'description' => NULL,
            'fixed' => 0,
            'allow_payment' => 0,
            'permission' => NULL,
        ),
        'fieldMeta' => 
        array (
            'name' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '255',
                'null' => false,
            ),
            'order' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'attributes' => 'unsigned',
                'default' => 0,
            ),
            'description' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '500',
                'null' => true,
            ),
            'fixed' => 
            array (
                'dbtype' => 'tinyint',
                'phptype' => 'integer',
                'precision' => '1',
                'null' => false,
                'attributes' => 'unsigned',
                'default' => 0,
            ),
            'allow_payment' => 
            array (
                'dbtype' => 'tinyint',
                'phptype' => 'integer',
                'precision' => '1',
                'null' => false,
                'attributes' => 'unsigned',
                'default' => 0,
            ),
            'permission' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '50',
                'null' => true,
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
        'aggregates' => 
        array (
            'Orders' => 
            array (
                'class' => '\\Brevis\\Model\\Order',
                'local' => 'id',
                'foreign' => 'status_id',
                'cardinality' => 'many',
            ),
        ),
    );
}
