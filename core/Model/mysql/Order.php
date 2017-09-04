<?php
namespace Brevis\Model\mysql;

use xPDO\xPDO;

class Order extends \Brevis\Model\Order
{

    public static $metaMap = array (
        'package' => 'Brevis\\Model',
        'version' => '3.0',
        'table' => 'orders',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'fields' => 
        array (
            'item_id' => 0,
            'item_name' => NULL,
            'item_price' => NULL,
            'item_code' => NULL,
            'user_id' => 0,
            'user_city_id' => 0,
            'sklad_id' => 0,
            'sklad_prefix' => NULL,
            'sklad_city_id' => 0,
            'cost' => 0,
            'createdon' => NULL,
            'updatedon' => NULL,
            'status_id' => 1,
            'is_paid' => 0,
            'remote_id' => 0,
            'comment' => NULL,
        ),
        'fieldMeta' => 
        array (
            'item_id' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'attributes' => 'unsigned',
                'default' => 0,
            ),
            'item_name' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '500',
                'null' => false,
            ),
            'item_price' => 
            array (
                'dbtype' => 'int',
                'precision' => '11',
                'phptype' => 'integer',
                'null' => false,
                'attributes' => 'unsigned',
            ),
            'item_code' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '16',
                'null' => false,
            ),
            'user_id' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'attributes' => 'unsigned',
                'default' => 0,
            ),
            'user_city_id' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'attributes' => 'unsigned',
                'default' => 0,
            ),
            'sklad_id' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'attributes' => 'unsigned',
                'default' => 0,
            ),
            'sklad_prefix' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '4',
                'phptype' => 'string',
                'null' => false,
            ),
            'sklad_city_id' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'attributes' => 'unsigned',
                'default' => 0,
            ),
            'cost' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'attributes' => 'unsigned',
                'default' => 0,
            ),
            'createdon' => 
            array (
                'dbtype' => 'datetime',
                'phptype' => 'string',
                'null' => false,
            ),
            'updatedon' => 
            array (
                'dbtype' => 'datetime',
                'phptype' => 'string',
                'null' => true,
            ),
            'status_id' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'attributes' => 'unsigned',
                'default' => 1,
            ),
            'is_paid' => 
            array (
                'dbtype' => 'tinyint',
                'phptype' => 'integer',
                'precision' => '1',
                'null' => false,
                'attributes' => 'unsigned',
                'default' => 0,
            ),
            'remote_id' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'attributes' => 'unsigned',
                'default' => 0,
            ),
            'comment' => 
            array (
                'dbtype' => 'text',
                'phptype' => 'string',
                'null' => true,
            ),
        ),
        'composites' => 
        array (
            'Payments' => 
            array (
                'class' => '\\Brevis\\Model\\Payments',
                'local' => 'id',
                'foreign' => 'order_id',
                'cardinality' => 'many',
                'owner' => 'local',
            ),
            'Fees' => 
            array (
                'class' => '\\Brevis\\Model\\Fee',
                'local' => 'id',
                'foreign' => 'order_id',
                'cardinality' => 'many',
                'owner' => 'local',
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
            'Item' => 
            array (
                'class' => '\\Brevis\\Model\\Item',
                'local' => 'item_id',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
            'Status' => 
            array (
                'class' => '\\Brevis\\Model\\OrderStatus',
                'local' => 'status_id',
                'foreign' => 'id',
                'cardinality' => 'one',
            ),
        ),
    );
}
